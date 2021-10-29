/**
 * @file    executor.cc
 * @author  liugang(liugang@ict.ac.cn)
 * @version 0.1
 *
 * @section DESCRIPTION
 *  
 * definition of executor
 *
 */
#include "executor.h"
#include "utils.h"
extern const char *CompareToken[];

Operator* Executor::planner(SelectQuery *query)
{
    assert(query->select_number>0 && query->from_number>0 && "No table selected.\n");
    //--------Parse and Sema-------- 
    // Parse conditions
    char CondValid[4] = {1,1,1,1};
    int64_t colrank;
    // for Scan
    std::list<Operator*> OpList;
    for(int i=0;i<query->from_number;i++){
        RowTable *rt = (RowTable*)g_catalog.getObjByName(query->from_table[i].name);
        OpConditions inConds;
        for(int j=0;j<query->where.condition_num;j++){
            OpCondition inCond;
            if(CondValid[j] && (colrank = getColRank(rt,query->where.condition[j].column.name))>=0){
                if(query->where.condition[j].compare != LINK){
                    inCond.isLink = 0;
                    strncpy(inCond.value,query->where.condition[j].value,128);
                }
                else{
                    continue; //TODO: in-table filter like o_id1=o_id2 ?
                }
                inCond.colrank1 = colrank;
                inCond.compare = query->where.condition[j].compare;
                inConds.push_back(inCond);
                CondValid[j] = 0;
            }
        }
        // Optimize: Push down Prject
        ColRanks prjs;
        for(int j=0;j<query->where.condition_num;j++){
            if(CondValid[j]){
                if((colrank = getColRank(rt,query->where.condition[j].column.name))>=0)
                    prjs.push_back(colrank);
                if(query->where.condition[j].compare == LINK && (colrank = getColRank(rt,query->where.condition[j].value))>=0)
                    prjs.push_back(colrank);
            }
        }
        for(int j=0;j<query->having.condition_num;j++){
            if((colrank = getColRank(rt,query->having.condition[j].column.name))>=0)
                prjs.push_back(colrank); //FIXME:LINK?
            if(query->having.condition[j].compare == LINK && (colrank = getColRank(rt,query->having.condition[j].value))>=0)
                prjs.push_back(colrank);
        }
        for(int j=0;j<query->orderby_number;j++){
            if((colrank = getColRank(rt,query->orderby[j].name))>=0)
                prjs.push_back(colrank);
        }
        for(int j=0;j<query->groupby_number;j++){
            if((colrank = getColRank(rt,query->groupby[j].name))>=0)
                prjs.push_back(colrank);
        }
        for(int j=0;j<query->select_number;j++){
            if((colrank = getColRank(rt,query->select_column[j].name))>=0)
                prjs.push_back(colrank);
        }
        sort(prjs.begin(),prjs.end());
        prjs.erase(unique(prjs.begin(),prjs.end()),prjs.end());
        parseValue(inConds,rt);
        if(!prjs.size()) prjs.push_back(0); //at least one for product
        Index* idx = findIndexOne(rt,inConds);
        Operator *OpScan;
        if(idx) {
            OpScan= new IndexScan(rt,inConds,prjs,idx);
            ((IndexScan*)OpScan)->genIdxConds();
        }
        else OpScan = new SeqScan(rt,inConds,prjs);
        OpList.push_back(OpScan);
    }
    // for equal-join (Not support inequal join)
    // Note: equal-join on multiple cols - use multi-index- has not tested.
    for(int j=0;j<query->where.condition_num;j++){
        if(!CondValid[j] || query->where.condition[j].compare != LINK) continue;
        OpConditions JoinConds;
        OpCondition inCond;
        int64_t colid1,colid2;
        Operator *prior1=nullptr, *prior2=nullptr;
        list<Operator*>::iterator it1, it2;
        colid1 = ((Column*)g_catalog.getObjByName(query->where.condition[j].column.name))->getOid();
        colid2 = ((Column*)g_catalog.getObjByName(query->where.condition[j].value))->getOid();
        for(auto it=OpList.begin();it!=OpList.end();it++){
            Operator *Op = *it;
            if(prior1==nullptr && (inCond.colrank1 = Op->getResult()->getColumnRank(colid1))>=0){
                prior1 = Op, it1 = it;
            }
            if(prior2==nullptr && (inCond.colrank2 = Op->getResult()->getColumnRank(colid2))>=0){
                prior2 = Op, it2 = it;
            }
            if(prior1!=nullptr && prior2!=nullptr) break;
        }
        assert(prior1 != nullptr && prior2 != nullptr && "Join Condition not found");
        inCond.isLink = 1;
        inCond.compare = EQ;
        assert(prior1 != prior2);
        JoinConds.push_back(inCond);
        CondValid[j] = 0;
        //Find other probable equal-join conditions
        for(int k=0;k<query->where.condition_num;k++){
            OpCondition inCond;
            if(CondValid[k] && query->where.condition[k].compare == LINK){
                if((inCond.colrank1 = getColRank(prior1->getResult(),query->where.condition[k].column.name))>=0
                &&(inCond.colrank2 = getColRank(prior2->getResult(),query->where.condition[k].value))>=0);
                else if((inCond.colrank2 = getColRank(prior2->getResult(),query->where.condition[k].column.name))>=0
                &&(inCond.colrank1 = getColRank(prior1->getResult(),query->where.condition[k].value))>=0);
                else continue;
                inCond.compare = EQ;
                inCond.isLink = 1;
                JoinConds.push_back(inCond);
                CondValid[k] = 0;
            }
        }
        Join *OpJoin;
        Index* idx = nullptr;
        SeqScan*  test = nullptr;
        // Search index
        if((test = dynamic_cast<SeqScan*>(prior2)) && (idx=findIndexOne(test->getResult(),JoinConds,test->getFromT(),1)))
        {
            IndexScan* newop = new IndexScan(test->getFromT(),test->getInConds(),test->getColRanks(),idx);
            delete prior2;
            prior2 = newop;
            OpJoin = new IndexJoin(prior1,prior2,JoinConds,idx);
        }
        else {
            OpConditions reverse = JoinConds;
            for(auto &inCond : reverse)
                std::swap(inCond.colrank1,inCond.colrank2);
            if((test = dynamic_cast<SeqScan*>(prior1)) && (idx=findIndexOne(test->getResult(),reverse,test->getFromT(),1))){
                IndexScan* newop = new IndexScan(test->getFromT(),test->getInConds(),test->getColRanks(),idx);
                delete prior1;
                prior1 = newop;
                OpJoin = new IndexJoin(prior2,prior1,reverse,idx);
            }
            else OpJoin = new HashJoin(prior1,prior2,JoinConds);
        } 
        OpList.erase(it1);
        OpList.erase(it2);
        OpList.push_back(OpJoin);
    }
    auto size = OpList.size();
    assert(size==1 && "Should conduct product");
    //for Groupby and Aggr
    Operator* top = OpList.front();
    vector<AggrInfo_t> Aggrs; AggrInfo_t aggr;
    for(int i=0;i<query->select_number;i++)
        if(query->select_column[i].aggrerate_method!=NONE_AM){
            aggr = genAggr(query->select_column[i],top->getResult());
            Aggrs.push_back(aggr);
        }
    for(int i=0;i<query->having.condition_num;i++)
        if(query->having.condition[i].column.aggrerate_method!=NONE_AM){
            aggr = genAggr(query->having.condition[i].column,top->getResult());
            if(!isExist(Aggrs,aggr)) Aggrs.push_back(aggr);
        }
    for(int i=0;i<query->orderby_number;i++)
        if(query->orderby[i].aggrerate_method!=NONE_AM){
            aggr = genAggr(query->orderby[i],top->getResult());
            if(!isExist(Aggrs,aggr)) Aggrs.push_back(aggr);
        }
            
    GroupbyAggr* OpGbaggr = nullptr;
    vector<AggrerateMethod> AM;
    
    ColRanks gb;
    for(int i=0;i<query->groupby_number;i++)
        gb.push_back(getColRank(top->getResult(),query->groupby[i].name));
    if(Aggrs.size()>0 || gb.size()>0){
        OpGbaggr = new GroupbyAggr(top,gb,Aggrs);
        top = OpGbaggr;
        OpConditions havs;
        for(int i=0;i<query->having.condition_num;i++){
            OpCondition cond;
            cond.colrank1 = OpGbaggr->getColRank(query->having.condition[i].column);
            assert(query->having.condition[i].compare!=LINK);
            cond.compare = query->having.condition[i].compare;
            cond.isLink = 0;
            strncpy(cond.value,query->having.condition[i].value,128);
            havs.push_back(cond);
        }
        parseValue(havs,top->getResult());
        OpGbaggr->setInConds(havs);
        AM = OpGbaggr->getAggrCols();
    }
    
    //for orderby
    ColRanks orders;
    for(int j=0;j<query->orderby_number;j++){
        if(OpGbaggr != nullptr)
            orders.push_back(OpGbaggr->getColRank(query->orderby[j]));
        else 
            orders.push_back(getColRank(top->getResult(),query->orderby[j].name));
    }
    if(orders.size()>0)
        top = new Orderby(top,orders,AM);

    //for project
    ColRanks prjs;
    for(int j=0;j<query->select_number;j++){
        if(OpGbaggr != nullptr)
            prjs.push_back(OpGbaggr->getColRank(query->select_column[j]));
        else 
            prjs.push_back(getColRank(top->getResult(),query->select_column[j].name));
    }
    Project* ProjectOp = new Project(top,prjs,AM);
    top = ProjectOp;
    
    return top;
}
int Executor::exec(SelectQuery *query, ResultTable *result)
{
    if(!query) return 0;
    Operator* top = planner(query);
    top->explain(3);
    RowTable * rt = top->getResult();
    result->init(rt->getRPattern().getDtype(),rt->getRPattern().getColNum(),1<<17);
    
    void *row = top->getRowBuf();
    assert(top->open());
    while(top->getNext()){
        result->appendRow(row);
    }
    printf("Total: %d lines\n",result->row_number);
    top->close();
    delete top;
    return 1;
}

int Executor::close() 
{  
    return 0;
}

//Operator
Operator::Operator(Operator *p) : prior(p), row_buf(nullptr)
{
    result = new RowTable(999, "OpTempTable");
    result->init();
}

bool Operator::do_filter(void* data,OpConditions& inConds, RowTable* rt)
{
    for(auto &inCond : inConds){
        char *value;
        BasicType * dtype = rt->getRPattern().getColumnType(inCond.colrank1);
        if(inCond.isLink)
            value = (char*)data + rt->getRPattern().getColumnOffset(inCond.colrank2);
        else value = inCond.value;
        char *LHS = (char*)data + rt->getRPattern().getColumnOffset(inCond.colrank1);
        if(compDtype(LHS,value,inCond.compare,dtype)==false) 
            return false;
    }
    return true;
}
bool Operator::do_project(void* dest, void* src, RowTable* rt_dest,RowTable* rt_src, ColRanks &prjs)
{
    int i = 0;
    for(auto colrank : prjs){
        int64_t of = rt_src->getRPattern().getColumnOffset(colrank);
        char* p = (char*)src + of;
        rt_dest->updateCol((char*)dest,i,p);
        i++;
    }
    return true;
}
Operator::~Operator()
{
    mfree(row_buf,result->getRPattern().getRowSize());
    if(dynamic_cast<Orderby*>(this)!=nullptr){
        result->shut();
        delete result;
    }
    if(prior) delete prior;
}

//Scan
Scan::Scan(RowTable *rt, OpConditions &inConds,ColRanks &prjs)
    :Operator(nullptr),fromT(rt),inConds(move(inConds)),prjs(move(prjs))
{
    result->initByPrj(rt,this->prjs);
    row_buf = (char*)mmalloc(result->getRPattern().getRowSize());
}
void Scan::explain(int indent)
{
    RowTable* rt = fromT;
    printf("From %s ",rt->getOname());
    printConds(inConds,rt,rt);
    if(prjs.size()) printf(" Project:");
    for(auto rank : prjs){
        printf(" %s",getColName(rt,rank));
    }
    printf("\n");
}
Scan::~Scan()
{}

//SeqScan
SeqScan::SeqScan(RowTable *rt, OpConditions &inConds,ColRanks &prjs)
    :Scan(rt,inConds,prjs)
{}
bool SeqScan::open()
{
    line = 0;
    return true;
}
bool SeqScan::getNext()
{
    char *p = nullptr;
    for(;line<fromT->getRecordNum();line++){
        if(fromT->access(line,p) && do_filter(p,inConds,fromT)){
            do_project(row_buf,p,result,fromT,prjs);
            line++;
            return true;
        }
    }
    return false;
}
bool SeqScan::close() {return true; }
void SeqScan::explain(int indent)
{
    printSpace(indent);
    printf("SeqScan: ");
    Scan::explain(indent);
}

//IndexScan
IndexScan::IndexScan(RowTable *rt, OpConditions &inConds,ColRanks &prjs,Index* index)
    :Scan(rt,inConds,prjs)
{
    idx = index;
    if(idx->getIType()==HASHINDEX) info = &hf;
    else {
        printf("USE PB INDEX!\n");
        info = &pf;
    }
    idxVal = (void**)mmalloc(idx->getIKey().getKey().size()*sizeof(char*));
}

void IndexScan::genIdxConds()
{
    auto keys = idx->getIKey().getKey();
    int i = 0;
    for(auto k:keys){
        for(auto it=inConds.begin();it!=inConds.end();it++){
            auto cond = *it;
            if(fromT->getColOid(cond.colrank1) == k && cond.isLink == 0 && cond.compare == EQ){
                idxConds.push_back(cond);
                it = inConds.erase(it);
                idxVal[i++] = idxConds.back().value;
                break;
            }
        }
    }
}
void IndexScan::setKeyVal(void **data)
{
    size_t size = idx->getIKey().getKey().size() * sizeof(void*);
    memcpy(idxVal,data,size);
}
bool IndexScan::open()
{
    return idx->set_ls(idxVal,NULL,&info);
}
bool IndexScan::getNext()
{
    void *q = nullptr;
    while(idx->lookup(idxVal,&info,q)){
        if(do_filter(q,inConds,fromT)){
            do_project(row_buf,q,result,fromT,prjs);
            return true;
        }
    }
    return false;
}
bool IndexScan::close() {return true; }
void IndexScan::explain(int indent)
{
    printSpace(indent);
    printf("IndexScan: Index: ");
    for(auto col:idx->getIKey().getKey())
        printf("%s ",getColName(fromT,fromT->getColumnRank(col)));
    Scan::explain(indent);
}
IndexScan::~IndexScan()
{
    mfree(idxVal,idx->getIKey().getKey().size()*sizeof(char*));
}

//Join
Join::Join(Operator *p1, Operator *p2)
    :Operator(p1),prior2(p2),outerLast(true),outerRes(nullptr)
{
    result->initByMerge(prior->getResult(),prior2->getResult());
    row_buf = (char*)mmalloc(result->getRPattern().getRowSize());
}
void Join::writeRow(void* data1, void* data2)
{
    int64_t size1 = prior->getResult()->getRPattern().getRowSize();
    memcpy(row_buf,data1,size1);
    memcpy(row_buf+size1,data2,prior2->getResult()->getRPattern().getRowSize());
}
void Join::explain(int indent)
{
    printf("Key: ");
    printConds(JoinConds,prior->getResult(),prior2->getResult(),0);
    printConds(inConds,result,result);
    printf("\n");
    prior->explain(indent+2);
    prior2->explain(indent+2);
}
Join::~Join()
{
    delete prior2;
}

HashJoin::HashJoin(Operator *p1, Operator *p2,OpConditions &JoinConds)
    :Join(p1,p2)
{
    this->JoinConds = JoinConds;
}

bool HashJoin::open()
{
    bool ret = prior->open() && prior2->open();
    if(!ret) return false;
    tempT = new ResultTable();
    RPattern & rp = prior2->getResult()->getRPattern();
    tempT->init(rp.getDtype(),rp.getColNum(),1<<18);
    void *p = prior2->getRowBuf();
    while(prior2->getNext()){
        tempT->appendRow(p);
    }
    prior2->close();
    Key key;
    ColRanks cols;
    for(auto& JoinCond : JoinConds)
        cols.push_back(prior2->getResult()->getColOid(JoinCond.colrank2));
    key.set(cols);
    size_t keySize = sizeof(void*) * cols.size();
    idx = new HashIndex(777,"tempHashJoinIndex",key);
    idx->init();
    idx->setCellCap(15);
    int64_t *offset = (int64_t*)mmalloc(keySize);
    keyOffset = (int64_t*)mmalloc(keySize);
    keyVal = (void**)mmalloc(keySize);
    
    for(size_t i=0;i<cols.size();i++){
        offset[i] = rp.getColumnOffset(JoinConds[i].colrank2);
        keyOffset[i] = prior->getResult()->getRPattern().getColumnOffset(JoinConds[i].colrank1);
        idx->addIndexDTpye(rp.getColumnType(JoinConds[i].colrank2),offset[i]);
    }
    idx->finish();
    
    for(int i=0;i<tempT->row_number;i++){
        char *p_in = tempT->getRow(i);
        for(size_t i=0;i<cols.size();i++)
            keyVal[i] = p_in + offset[i];
        idx->insert(keyVal,p_in);
    }
    outerLast = true;
    outerRes = (char*)prior->getRowBuf();
    for(size_t i=0;i<idx->getIKey().getKey().size();i++)
        keyVal[i] = outerRes + keyOffset[i];
    mfree(offset,keySize);
    return true;
}
bool HashJoin::getNext()
{
    void *in = nullptr;
    while(1){
        if(outerLast){
            if(!prior->getNext()) return false;
            outerLast = false;
            idx->set_ls(keyVal,NULL,&hf);
        }
        if(idx->lookup(keyVal,&hf,in)){
            writeRow(outerRes,in);
            return true;
        }
        else 
            outerLast = true;
    }
}
bool HashJoin::close()
{
    mfree(keyOffset,idx->getIKey().getKey().size()*sizeof(int64_t));
    mfree(keyVal,idx->getIKey().getKey().size()*sizeof(void*));
    idx->shut();
    delete idx;
    tempT->shut();
    delete tempT;
    return prior->close();
}
void HashJoin::explain(int indent)
{
    printSpace(indent);
    printf("HashJoin: ");
    Join::explain(indent);
}

//IndexJoin
// JoinConds will be taken part into conds for index and conds for filter
IndexJoin::IndexJoin(Operator *pOuter, Operator *pInner, OpConditions &JoinConds, Index* index)
    :Join(pOuter,pInner), idx(index)
{
    auto keys = idx->getIKey().getKey();
    for(auto k:keys){
        for(auto it=JoinConds.begin();it!=JoinConds.end();it++){
            auto cond = *it;
            if(cond.isLink > 0 && pInner->getResult()->getColOid(cond.colrank2) == k && cond.compare == EQ){
                this->JoinConds.push_back(cond);
                it = JoinConds.erase(it);
                break;
            }
        }
    }
    this->inConds = JoinConds;
    int64_t p1NUM = prior->getResult()->getRPattern().getColNum();
    for(auto inCond:this->inConds){
        inCond.colrank2 += p1NUM;
    }
}
bool IndexJoin::open()
{
    outerLast = true;
    int keyNum = idx->getIKey().getKey().size();
    keyOffset = (int64_t*)mmalloc(keyNum * sizeof(int64_t));
    keyVal = (void**)mmalloc(keyNum * sizeof(void*));
    for(int i=0;i<keyNum;i++){
        keyOffset[i] = prior->getResult()->getRPattern().getColumnOffset(JoinConds[i].colrank1);
    }
    outerRes = (char*)prior->getRowBuf();
    for(size_t i=0;i<idx->getIKey().getKey().size();i++)
        keyVal[i] = outerRes + keyOffset[i];
    return prior->open(); //prior2 open at getNext!
}
bool IndexJoin::getNext()
{
    void *in = prior2->getRowBuf();
    IndexScan *innerP = dynamic_cast<IndexScan*>(prior2);
    while(1){
        if(outerLast){
            if(!prior->getNext()) return false;
            outerLast = false;
            innerP->setKeyVal(keyVal);
            if(!innerP->open()) return false;
        }
        if(innerP->getNext() && do_filter(in,inConds,result)) break;
        else {
            outerLast = true;
            innerP->close();
        }
    }
    writeRow(outerRes,in);
    return true;
}
bool IndexJoin::close()
{
    mfree(keyOffset,idx->getIKey().getKey().size()*sizeof(int64_t));
    mfree(keyVal,idx->getIKey().getKey().size()*sizeof(void*));
    return prior->close(); //prior2 close at getNext!
}
void IndexJoin::explain(int indent)
{
    printSpace(indent);
    printf("IndexJoin: ");
    Join::explain(indent);
}

//Project
Project::Project(Operator *prior,ColRanks &prjs,vector<AggrerateMethod>& AM)
    :Operator(prior),prjs(move(prjs)),AM(AM)
{
    result->initByPrj(prior->getResult(),this->prjs);
    row_buf = (char*)mmalloc(result->getRPattern().getRowSize());
}
bool Project::open()
{
    return prior->open();
}
bool Project::getNext()
{
    void *src = prior->getRowBuf();
    if(!prior->getNext()) return false;
    do_project(row_buf,src,result,prior->getResult(),prjs);
    return true;
}
bool Project::close()
{
    return prior->close();
}
void Project::explain(int indent)
{
    for(int i=0;i<indent;i++)
        putchar(' ');
    printf("Project: ");
    for(auto rank : prjs){
        if(AM.size()==prjs.size()&& AM[rank]!=NONE_AM)
            printf("%s:",Aggrtoken[AM[rank]]);
        printf("%s ",getColName(prior->getResult(),rank));
        printf(" ");
    }
    printf("\n");
    prior->explain(indent+2);
}
Project::~Project()
{
}

GroupbyAggr::GroupbyAggr(Operator* prior, ColRanks &gbranks, vector<AggrInfo_t>&aggrinfo)
    :Operator(prior),groupbyRanks(gbranks), AggrInfo(aggrinfo),tempT(nullptr),aggrcols(gbranks.size(),NONE_AM)
{
    assert(AggrInfo.size()>0);
    ColRanks prjs = groupbyRanks;
    for(const auto &i : AggrInfo)
        prjs.push_back(i.colrank);
    for(auto col:prjs)
        result->addColumn(prior->getResult()->getColOid(col));
    RPattern& pattern = result->getRPattern();
    pattern.init(prjs.size());
    MStorage& storage = result->getMStorage();
    for(auto col:groupbyRanks){
        BasicType* dt = prior->getResult()->getRPattern().getColumnType(col);
        pattern.addColumn(dt);
        dest_types.push_back(dt);
    }
    for(auto &i : AggrInfo){
        i.dtype = prior->getResult()->getRPattern().getColumnType(i.colrank);
        BasicType* col_type;
        if(i.AggrMethod == COUNT || (i.AggrMethod == SUM && i.dtype->isIntType()))
            col_type=&intType; 
        else if((i.AggrMethod == SUM && i.dtype->isFloatType()) || i.AggrMethod == AVG)
            col_type=&floatType;
        else
            col_type=(i.dtype);
        pattern.addColumn(col_type);
        dest_types.push_back(col_type);
        if(i.AggrMethod == AVG)
            dest_types.push_back(&intType);
    }
    storage.init(pattern.getRowSize());
    row_buf = (char*)mmalloc(result->getRPattern().getRowSize());
    for(auto &i:AggrInfo)
        aggrcols.push_back(i.AggrMethod);
}

void GroupbyAggr::do_aggr_acc(void* dest, void* src)
{
    void* ndest = dest;
    BasicType** dtype = &dest_types[groupbyRanks.size()];
    for(size_t i=0;i<AggrInfo.size();i++){
        void* nsrc = (char*)src + prior->getResult()->getRPattern().getColumnOffset(AggrInfo[i].colrank);
        double acc;
        int64_t *cnt;
        switch(AggrInfo[i].AggrMethod){
            case(SUM):
                if(AggrInfo[i].dtype->isIntType())
                    *(int64_t*)ndest = *(int64_t*)ndest + AggrInfo[i].dtype->getIntVal(nsrc);
                else
                    *(double*)ndest = *(double*)ndest + AggrInfo[i].dtype->getFloatVal(nsrc);
                break;
            case(MIN):
                if(AggrInfo[i].dtype->cmpLT(nsrc,ndest))
                    AggrInfo[i].dtype->copy(ndest,nsrc);
                break;
            case(MAX):
                if(AggrInfo[i].dtype->cmpGT(nsrc,ndest))
                    AggrInfo[i].dtype->copy(ndest,nsrc);
                break;
            case(AVG):
                if(AggrInfo[i].dtype->isIntType())
                    acc = (double)AggrInfo[i].dtype->getIntVal(nsrc);
                else 
                    acc = AggrInfo[i].dtype->getFloatVal(nsrc);
                cnt = (int64_t*)(((double*)ndest)+1);
                *(double*)ndest = (*(double*)ndest* *cnt + acc)/(*cnt+1);
                ndest = (char*)ndest + (*dtype)->getTypeSize();
                dtype++;
            case(COUNT):
                *(int64_t*)ndest = *(int64_t*)ndest + 1;
                break;
            default: assert(0);
        }
        ndest = (char*)ndest + (*dtype)->getTypeSize();
        dtype++;
    }
}
void GroupbyAggr::do_aggr_init(void* dest, void* src)
{
    BasicType** dtype = &dest_types[0];
    for(size_t i=0;i<groupbyRanks.size();i++){
        void* nsrc = (char*)src + prior->getResult()->getRPattern().getColumnOffset(groupbyRanks[i]);
        prior->getResult()->getRPattern().getColumnType(groupbyRanks[i])->copy(dest,nsrc);
        dest = (char*)dest + (*dtype)->getTypeSize();
        dtype++;
    }
    for(size_t i=0;i<AggrInfo.size();i++){
        void* nsrc = (char*)src + prior->getResult()->getRPattern().getColumnOffset(AggrInfo[i].colrank);
        switch(AggrInfo[i].AggrMethod){
            case(MIN):
            case(MAX):
                AggrInfo[i].dtype->copy(dest,nsrc);
                break;
            case(SUM):
                if(AggrInfo[i].dtype->isIntType())
                    *(int64_t*)dest = AggrInfo[i].dtype->getIntVal(nsrc);
                else
                    *(double*)dest = AggrInfo[i].dtype->getFloatVal(nsrc);
                break;
            case(AVG):
                if(AggrInfo[i].dtype->isIntType())
                    *(double*)dest = (double)AggrInfo[i].dtype->getIntVal(nsrc);
                else
                    *(double*)dest = AggrInfo[i].dtype->getFloatVal(nsrc);
                dest = (char*)dest + (*dtype)->getTypeSize();
                dtype++;
            case(COUNT):
                *(int64_t*)dest = 1;
                break;
            default: assert(0);
        }
        dest = (char*)dest + (*dtype)->getTypeSize();
        dtype++;
    }
}
bool GroupbyAggr::open()
{
    if(!prior->open()) return false;
    tempT = new ResultTable();
    tempT->init(&dest_types[0],dest_types.size(),1<<17);
    void *p = prior->getRowBuf();
    if(groupbyRanks.size()>0){
        Key key;
        ColRanks cols;
        for(size_t i = 0;i<groupbyRanks.size();i++)
            cols.push_back(i);
        key.set(cols);
        size_t keySize = sizeof(void*) * cols.size();
        idx = new HashIndex(666,"tempGroupbyIndex",key);
        idx->init();
        idx->setCellCap(15);
        int64_t *offset = (int64_t*)mmalloc(keySize);
        offset[0] = 0;
        for(size_t i=0;i<cols.size();i++){
            if(i>0) offset[i] = offset[i-1] + dest_types[i-1]->getTypeSize();
            idx->addIndexDTpye(dest_types[i],offset[i]);
        }
        idx->finish();
        HashInfo hf;

        int64_t colsSize = offset[cols.size()-1] + dest_types[cols.size()-1]->getTypeSize();
        char *pbuf = (char*)mmalloc(colsSize);
        void *q = nullptr;
        void** keyVal = (void**)mmalloc(keySize);
        for(size_t i=0;i<cols.size();i++)
            keyVal[i] = pbuf + offset[i];

        while(prior->getNext()){
            for(size_t i=0;i<groupbyRanks.size();i++){
                char* src = (char*)p + prior->getResult()->getRPattern().getColumnOffset(groupbyRanks[i]);
                prior->getResult()->getRPattern().getColumnType(groupbyRanks[i])->copy(pbuf+offset[i],src);
            }
            idx->set_ls(keyVal,nullptr,&hf);
            if(idx->lookup(keyVal,&hf,q)){
                do_aggr_acc((char*)q+colsSize,p);
            }
            else{
                void* dest = tempT->appendRow();
                do_aggr_init(dest,p);
                assert(idx->insert(keyVal,dest));
            }
        }
        mfree(offset,keySize);
        mfree(keyVal,keySize);
        idx->shut();
        delete idx;
    }
    else{
        if(prior->getNext()){
            void* dest = tempT->appendRow();
            do_aggr_init(dest,p);
            while(prior->getNext()){
                do_aggr_acc(dest,p);
            }
        }
    }
    prior->close();
    line = 0;
    return true;
}
bool GroupbyAggr::getNext()
{
RETRY:
    if(line>=tempT->row_number) return false;
    char* dest = row_buf;
    char* src = tempT->getRow(line);
    for(size_t i=0;i<dest_types.size();i++){
        dest_types[i]->copy(dest,src);
        src += dest_types[i]->getTypeSize();
        dest += dest_types[i]->getTypeSize();
        if(i>=groupbyRanks.size() && AggrInfo[i-groupbyRanks.size()].AggrMethod==AVG){
            i++;
            src += dest_types[i]->getTypeSize();
        }
    }
    line++;
    if(!do_filter(row_buf,inConds,result)) goto RETRY;
    return true;
}
bool GroupbyAggr::close()
{
    tempT->shut();
    delete tempT;
    return true; //prior close at open
}

void GroupbyAggr::explain(int indent)
{
    printSpace(indent);
    for(size_t i=0;i<groupbyRanks.size();i++){
        if(i==0) printf("GroupBy: ");
        printf("%s ",getColName(prior->getResult(),i));
    }
    printf("Aggregate: ");
    for(auto &i:AggrInfo){
        printf("%s(",Aggrtoken[i.AggrMethod]);
        printf("%s) ",getColName(prior->getResult(),i.colrank));
    }
    printConds(inConds,result,result,1,&getAggrCols());
    
    printf("\n");
    prior->explain(indent+2);
}
int64_t GroupbyAggr::getColRank(RequestColumn& r)
{   
    if(r.aggrerate_method==NONE_AM)
        return exec::getColRank(result,r.name);
    else{
        int64_t rank = exec::getColRank(prior->getResult(),r.name);
        for(size_t i=0;i<AggrInfo.size();i++){
            if(AggrInfo[i].colrank == rank && AggrInfo[i].AggrMethod==r.aggrerate_method)
                return i+groupbyRanks.size();
        }
        return -1;
    }
}
void GroupbyAggr::setInConds(OpConditions &conds)
{
    inConds = move(conds);
}
vector <AggrerateMethod>& GroupbyAggr::getAggrCols()
{
    return aggrcols;
}
GroupbyAggr::~GroupbyAggr()
{}

//Orderby
Orderby::Orderby(Operator* prior, ColRanks &cols,vector<AggrerateMethod>& AM)
    :Operator(prior), cols(cols), AM(AM)
{
    result = prior->getResult();
    row_buf = (char*)mmalloc(result->getRPattern().getRowSize());
}

bool Orderby::open()
{
    assert(prior->open());
    RPattern& rp = result->getRPattern();
    tempT = new ResultTable();
    tempT->init(rp.getDtype(),rp.getColNum(),1<<15);
    char *p = (char*)prior->getRowBuf();
    while(prior->getNext()){
        void *r = tempT->appendRow();
        q.push_back(r);
        memcpy(r,p,tempT->row_length);
    }
    prior->close();
    struct comp{
        vector<int64_t> offset;
        vector<BasicType*> bt;
        comp(vector<BasicType*>& bt, vector<int64_t>& offset):offset(offset),bt(bt) {}
        bool operator()(void* a, void* b) const{
            for(size_t i=0;i<offset.size();i++){
                if(bt[i]->cmpLT((char*)a + offset[i],(char*)b + offset[i]))
                    return true;
                else if(bt[i]->cmpGT((char*)a + offset[i],(char*)b + offset[i]))
                    return false;
            }
            return false;
        }
    };
    vector<BasicType*> bt;
    vector<int64_t> offset;
    for(auto col:cols){
        bt.push_back(rp.getColumnType(col));
        offset.push_back(rp.getColumnOffset(col));
    }
    sort(q.begin(),q.end(),comp(bt,offset));
    line = 0;
    return true;
}
bool Orderby::getNext()
{
    if(line<q.size()){
        memcpy(row_buf,q[line++],tempT->row_length);
        return true;
    }
    return false;
}
bool Orderby::close()
{
    tempT->shut();
    delete tempT;
    return true;
}
void Orderby::explain(int indent)
{
    printSpace(indent);
    printf("Order by: ");
    for(auto i:cols){
        if(AM.size()>0 && AM[i]!=NONE_AM)
            printf("%s:",Aggrtoken[AM[i]]);
        printf("%s ",getColName(result,i));
    }
    printf("\n");
    prior->explain(indent+2);
}
Orderby::~Orderby()
{}


//OpCondition
void OpCondition::print(RowTable *rt1, RowTable *rt2)
{
    printf("%s",getColName(rt1,colrank1));
    printf("%s",CompareToken[compare]);
    if(isLink){
        printf("%s ",getColName(rt2,colrank2));
    }
    else {
        char tmp[128];
        rt1->getRPattern().getColumnType(colrank1)->formatTxt(tmp,value);
        printf("%s ",tmp);
    }
}

//ResultTable
// note: you should guarantee that col_types is useable as long as this ResultTable in use, maybe you can new from operate memory, the best method is to use g_memory.
int ResultTable::init(BasicType *col_types[], int col_num, int64_t capicity) {
    isShut = 0;
    int typeSize = sizeof(BasicType *)*col_num;
    column_type = (BasicType**)mmalloc(typeSize);
    memcpy(column_type,col_types,typeSize);
    
    column_number = col_num;
    row_length = 0;
    buffer_size = capicity;
    buffer = (char*)mmalloc(capicity);

    int allocate_size = GLOBAL_MEMORY_MINIMUM;
    int require_size = sizeof(int)*column_number;
    while (allocate_size < require_size)
        allocate_size = allocate_size << 1;
    offset = (int*)mmalloc(allocate_size);
    offset_size = allocate_size;

    for(int ii = 0;ii < column_number;ii ++) {
        offset[ii] = row_length;
        row_length += column_type[ii]->getTypeSize(); 
    }
    row_capicity = (int)(capicity / row_length);
    row_number   = 0;
    return 0;
}

int ResultTable::appendRow(const void *row){
    void* dest = appendRow();
    memcpy(dest, row,row_length);
    return 0;
}
void* ResultTable::appendRow(){
    while(row_number == row_capicity)
        expand();
    return buffer+row_length*row_number++;
}
void ResultTable::expand()
{
    //printf("expand %d\n",row_capicity);
    int64_t new_capicity = buffer_size * 2;
    char *new_buffer = (char*)mmalloc(new_capicity);
    memcpy(new_buffer,buffer,buffer_size);
    mfree(buffer,buffer_size);
    buffer_size = new_capicity;
    buffer = new_buffer;
    row_capicity = (int)(buffer_size / row_length);
}

int ResultTable::print (void) {
    int row = 0;
    int ii = 0;
    char buffer[1024];
    char *p = NULL; 
    while(row < row_number) {
        for( ; ii < column_number-1; ii++) {
            p = getRC(row, ii);
            column_type[ii]->formatTxt(buffer, p);
            printf("%s\t", buffer);
        }
        p = getRC(row, ii);
        column_type[ii]->formatTxt(buffer, p);
        printf("%s\n", buffer);
        row ++; ii=0;
    }
    return row;
}

int ResultTable::dump(FILE *fp) {
    // write to file
    int row = 0;
    int ii = 0;
    char buffer[1024];
    char *p = NULL; 
    while(row < row_number) {
        for( ; ii < column_number-1; ii++) {
            p = getRC(row, ii);
            column_type[ii]->formatTxt(buffer, p);
            fprintf(fp,"%s\t", buffer);
        }
        p = getRC(row, ii);
        column_type[ii]->formatTxt(buffer, p);
        fprintf(fp,"%s\n", buffer);
        row ++; ii=0;
    }
    return row;
}

// this include checks, may decrease its speed
char* ResultTable::getRC(int row, int column) {
    return buffer+ row*row_length+ offset[column];
}
char* ResultTable::getRow(int row){
    return buffer + row*row_length;
}

int ResultTable::writeRC(int row, int column, void *data) {
    char *p = getRC (row,column);
    if (p==NULL) return 0;
    return column_type[column]->copy(p,data);
}

int ResultTable::shut (void) {
    // free memory
    if(isShut) return 0;
    isShut = 1;
    mfree(buffer, buffer_size);
    mfree(offset, offset_size);
    mfree(column_type,sizeof(BasicType*)*column_number);
    return 0;
}
const char *CompareToken[] = {
  "",
  "<",
  "<=",
  "=",
  "!=",
  ">",
  ">=",
  "JOIN",
  ""
};
const char *Aggrtoken[] = {
    "",
    "COUNT",
    "SUM",
    "AVG",
    "MAX",
    "MIN",
    ""
};
static bool (BasicType::*compFunc[MAX_CM])(void* data1, void* data2) = 
{
    nullptr,
    &BasicType::cmpLT,
    &BasicType::cmpLE,
    &BasicType::cmpEQ,
    &BasicType::cmpEQ,
    &BasicType::cmpGT,
    &BasicType::cmpGE,
    &BasicType::cmpEQ
};
static inline bool compDtype(void* data1,void* data2, CompareMethod CM,BasicType *dtype)
{
    bool ret = (dtype->*compFunc[CM])(data1,data2);
    if(CM != NE) return ret;
    return !ret;
}