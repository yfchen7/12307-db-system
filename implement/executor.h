/**
 * @file    executor.h
 * @author  liugang(liugang@ict.ac.cn)
 * @version 0.1
 *
 * @section DESCRIPTION
 *  
 * definition of executor
 *
 */

#ifndef _EXECUTOR_H
#define _EXECUTOR_H

#include "catalog.h"
#include "mymemory.h"
#include <list>
#include <vector>
#include <utility>
#include <algorithm>
using std::vector;
using std::list;
using std::move;
using std::sort;
using std::unique;

/** aggrerate method. */
enum AggrerateMethod {
    NONE_AM = 0, /**< none */
    COUNT,       /**< count of rows */
    SUM,         /**< sum of data */
    AVG,         /**< average of data */
    MAX,         /**< maximum of data */
    MIN,         /**< minimum of data */
    MAX_AM
};

/** compare method. */
enum CompareMethod {
    NONE_CM = 0,
    LT,        /**< less than */
    LE,        /**< less than or equal to */
    EQ,        /**< equal to */
    NE,        /**< not equal than */
    GT,        /**< greater than */
    GE,        /**< greater than or equal to */
    LINK,      /**< join */
    MAX_CM
};

/** definition of request column. */
struct RequestColumn {
    char name[128];    /**< name of column */
    AggrerateMethod aggrerate_method;  /** aggrerate method, could be NONE_AM  */
};

/** definition of request table. */
struct RequestTable {
    char name[128];    /** name of table */
};

/** definition of compare condition. */
struct Condition {
    RequestColumn column;   /**< which column */
    CompareMethod compare;  /**< which method */
    char value[128];        /**< the value to compare with, if compare==LINK,value is another column's name; else it's the column's value*/
};

/** definition of conditions. */
struct Conditions {
    int condition_num;      /**< number of condition in use */
    Condition condition[4]; /**< support maximum 4 & conditions */
};
/** definition of conditions after semantic analysis. */
struct OpCondition {
    int64_t colrank1;        /**< column1 rank*/
    int64_t colrank2;        /**< column2 rank */
    char isLink;              /**< isLink */
    CompareMethod compare;   /**< which method */
    char value[128];         /**< if not isLink, it's the column's value*/
    /**
     * print the condition information
     * @param rt1     rowtable that column1 belongs to
     * @param rt2     rowtable that column2 belongs to
     */
    void print(RowTable *rt1, RowTable *rt2);
};

/** definition of conditions after semantic analysis. */
typedef vector<OpCondition> OpConditions;

/** definition of ranks of columns */
typedef vector<int64_t> ColRanks;

/** definition of selectquery.  */
class SelectQuery {
  public:
    int64_t database_id;           /**< database to execute */
    int select_number;             /**< number of column to select */
    RequestColumn select_column[4];/**< columns to select, maximum 4 */ //May agg here
    int from_number;               /**< number of tables to select from */
    RequestTable from_table[4];    /**< tables to select from, maximum 4 */
    Conditions where;              /**< where meets conditions, maximum 4 & conditions */
    int groupby_number;            /**< number of columns to groupby */
    RequestColumn groupby[4];      /**< columns to groupby */ //No agg here?
    Conditions having;             /**< groupby conditions */
    int orderby_number;            /**< number of columns to orderby */
    RequestColumn orderby[4];      /**< columns to orderby */ //No agg hrer?
};  // class SelectQuery

/** definition of result table.  */
class ResultTable {
  public:
    int column_number;       /**< columns number that a result row consist of */
    BasicType **column_type; /**< each column data type */
    char *buffer;         /**< pointer of buffer alloced from g_memory */
    int64_t buffer_size;  /**< size of buffer, power of 2 */
    int row_length;       /**< length per result row */
    int row_number;       /**< current usage of rows */
    int row_capicity;     /**< maximum capicity of rows according to buffer size and length of row */
    int *offset;          /**< offset of each datatype */
    int offset_size;      /**< offset_size of each datatype */
    int isShut;          /**<Avoid muti-shut by runaimdb.cc */
    /**
     * init alloc memory and set initial value
     * @col_types array of column type pointers
     * @col_num   number of columns in this ResultTable
     * @param  capicity buffer_size, power of 2
     * @retval >0  success
     * @retval <=0  failure
     */
    int init(BasicType *col_types[],int col_num,int64_t capicity = 1024);
    /**
     * calculate the char pointer of data spcified by row and column id
     * you should set up column_type,then call init function
     * @param row    row id in result table
     * @param column column id in result table
     * @retval !=NULL pointer of a column
     * @retval ==NULL error
     */
    char* getRC(int row, int column);
    /**
     * write data to position row,column
     * @param row    row id in result table
     * @param column column id in result table
     * @data data pointer of a column
     * @retval !=NULL pointer of a column
     * @retval ==NULL error
     */
    int writeRC(int row, int column, void *data);
    /**
     * print result table, split by '\t', output a line per row 
     * @retval the number of rows printed
     */
    int print(void);
    /**
     * write to file with FILE *fp
     */
    int dump(FILE *fp);
    /**
     * free memory of this result table to g_memory
     */
    int shut(void);

    /**
     * append and write a row to this result table
     * @param data      pointer to record to write
     * @retval ==0      success
     * @retval !=0      error
     */
    int appendRow(const void *data);
    /**
     * alloc a new row to store data, 
     * if row number goes up to row_capicity,
     * call expand() to alloc more space. 
     * @retval !=NULL   pointer to the new row alloced
     * @retval ==NULL   error
     */
    void* appendRow();
    /**
     * get pointer of a row from its row id
     * @param row       id of the row we want to get
     * @retval !=NULL   pointer to the row
     * @retval ==NULL   error
     */
    char* getRow(int row);
    /**
     * alloc more memory when row number goes up to row_capicity,
     * it allocs a new_buffer of 2*buffer_size and copy buffer to it,
     * then free old buffer and change buffer pointer to new_buffer,
     * finally update buffer_size and row_capicity.
     * @retval !=NULL   pointer to the new row alloced
     * @retval ==NULL   error
     */
    void expand();
};  // class ResultTable


/** definition of operator. */
class Operator {
protected:
  RowTable *result;           /**< rowtable of result, it just provides the pattern of row */
  Operator *prior;            /**< the prior operator of this operator, may be NULL */
  char *row_buf;              /**< buffer to store a row of result */
  /**
   * do filter to a row
   * @param data    pointer to a row
   * @param inConds conditions of filter
   * @param rt      rowtable of the row
   * @retval true   success
   * @retval false  failure
   */
  bool do_filter(void* data,OpConditions& inConds,RowTable* rt);

  /**
   * do project to a row of the source rowtable and get a new row of the dest rowtable
   * @param dest    pointer to the dest row
   * @param src     pointer to the src row
   * @param rt_dest dest rowtable
   * @param rt_src  source rowtable
   * @param prjs    ranks of columns of project
   * @retval true   success
   * @retval false  failure
   */
  bool do_project(void* dest, void* src, RowTable* rt_dest,RowTable* rt_src, ColRanks &prjs);
public:
  /**
   * constructor. Initialize the result table and row_buffer.
   * @param p   prior of this operator    
   */
  Operator(Operator* p);
  
  /**
   * get result table    
   * @retval result   pointer of result rowtable
   */
  RowTable *getResult() {return result;}
  
  /**
   * get row buffer of this operator, it stores a row of result    
   * @retval row_buf  pointer to a row of result of this operator
   */
  void *getRowBuf() {return (void*)row_buf; }

  /**
   * init the operator and alloc resources  
   * @retval true     success
   * @retval false    failure
   */
  virtual bool open() {return prior->open(); }

  /**
   * write a row of result to its rowbuffer.
   * @retval true     success
   * @retval false    failure
   */
  virtual	bool getNext() {return prior->getNext(); }

  /**
   * close and release resources  
   * @retval true     success
   * @retval false    failure
   */
  virtual	bool close() {return prior->close(); }

  /**
   * print the operator tree
   * @param indent    indent while printing a operator
   */
  virtual void explain(int indent)=0;
  
  /**
   * destructor. Destory result table and row_buf.
   */
  virtual ~Operator();
};

/** definition of scan and filter. */
class Scan : public Operator{
protected:
  RowTable *fromT;        /**< rowtable of input */
  OpConditions inConds;   /**< filter conditions when doing scan */
  ColRanks prjs;          /**< ranks of columns we need in a query after pushing down projection */
public:
  /**
   * constructor.
   * @param rt        input rowtable(fromT)
   * @param inConds   filter conditions when doing scan
   * @param prjs      columns we need in the query
   */
  Scan(RowTable *rt, OpConditions &inConds,ColRanks &prjs);

  /**
   * get the input table    
   * @retval fromT    pointer to input rowtable
   */
  RowTable* getFromT() {return fromT; }
  
  /**
   * get filter conditions    
   * @retval inConds  filter conditions when doing scan
   */
  OpConditions &getInConds() {return inConds;}
  
  /**
   * get ranks of columns in this scan   
   * @retval prjs     ranks of columns in this scan
   */
  ColRanks &getColRanks() {return prjs;}

   /**
   * init scan operator and alloc resources  
   * @retval true     success
   * @retval false    failure
   */ 
  virtual bool open() {return true; }
  /**
   * write a row of result to its rowbuffer.
   * @retval true     success
   * @retval false    failure
   */
  virtual bool getNext() {return true; }
  /**
   * close and release resources  
   * @retval true     success
   * @retval false    failure
   */
  virtual bool close()  {return true; }
  /**
   * print the message of scan
   * @param indent    indent while printing scan operator
   */
  virtual void explain(int indent);
  /**
   * destructor.   
   */
  virtual ~Scan();
};

/** definition of indexscan and filter. */
class IndexScan : public Scan{
private:
  void **idxVal;            /**< point to index data[] */
  OpConditions idxConds;    /**< Save static index conditions */
  Index *idx;               /**< index of row_table we use */
  void* info;               /**< HashInfo pointer */
  HashInfo hf;              /**< hash information*/
  PbtreeInfo pf;            /**< pbtree information*/
public:
  /**
   * constructor.
   * @param rt        input rowtable(fromT)
   * @param inConds   filter conditions when doing scan
   * @param prjs      ranks of columns we need in the query
   * @param index     index of row_table we use
   */
  IndexScan(RowTable *rt, OpConditions &inConds,ColRanks &prjs,Index* index);

  /**
   * init indexscan operator, call set_ls() to setup for hash index lookup  
   * @retval true     success
   * @retval false    failure
   */ 
  bool open();

  /**
   * write this row of result to its rowbuffer.
   * @retval true     success
   * @retval false    come to end
   */
  bool getNext();

  /**
   * close
   * @retval true     success
   * @retval false    failure
   */
  bool close();

  /**
   * print the message of indexscan
   * @param indent    indent while printing scan operator
   */
  void explain(int indent);

  /**
   * it is called by indexjoin and reset idxVal for lookup of a record
   * @param data      point to index data[] to be assigned to  to idxVal
   */
  void setKeyVal(void** data);

  /**
   * it is called in tree building and set static idxConds and idxVal for index scan
   */
  void genIdxConds();
  /**
   * destructor.   
   */
  ~IndexScan();
};

/** definition of scan and filter. */
class SeqScan : public Scan{
protected:
  int64_t line;       /**< lines scaned */
public:
  /**
   * constructor.
   * @param rt        input rowtable(fromT)
   * @param inConds   filter conditions when doing scan
   * @param prjs      columns we need in a query
   */
  SeqScan(RowTable *rt, OpConditions &inConds,ColRanks &prjs);
  
  /**
   * init scan operator, set line=0
   * @retval true     success
   * @retval false    failure
   */ 
  bool open();

  /**
   * write this row of result to its rowbuffer.
   * @retval true     success
   * @retval false    come to end
   */
  bool getNext();

  /**
   * close
   * @retval true     success
   * @retval false    failure
   */
  bool close();

  /**
   * print the message of seqscan
   * @param indent    indent while printing scan operator
   */
  void explain(int indent);
};

/** definition of project.  */
class Project : public Operator {
protected:
  ColRanks prjs;                /**< ranks of columns we need in the query */
  vector<AggrerateMethod> AM;   /**< aggreration methods */
public:
  /**
   * constructor.
   * @param prior       prior operator
   * @param prjs        ranks of columns we need in the query
   * @param AM          aggreration methods
   */
  Project(Operator *prior,ColRanks &prjs,vector<AggrerateMethod>& AM);
  
  /**
   * init project operator and call its prior's open
   * @retval true     success
   * @retval false    failure
   */ 
  bool open();

  /**
   * write this row of result to its rowbuffer.
   * @retval true     success
   * @retval false    end of traversal
   */
  bool getNext();
  
  /**
   * close and call prior's close
   * @retval true     success
   * @retval false    failure
   */
  bool close();
    
  /**
   * print the message of project
   * @param indent    indent while printing project operator
   */
  void explain(int indent);
  
  /**
   * destructor.   
   */
  virtual ~Project();
};

/** definition of join.  */
class Join : public Operator {
protected:
  Operator *prior2;         /**< the other prior operator of join operator */
  OpConditions JoinConds;   /**< join conditions base on prior and prior2 with indexed columns */
  OpConditions inConds;     /**< filter conditions base on result */
  bool outerLast;           /**< to indicate whether the search for a record has ended  */
  char *outerRes;           /**< result of prior 1  */
  int64_t *keyOffset;       /**< offset of keys building hashindex in prior 1  */
  void **keyVal;            /**< indexVals of hash index  */

  /**
   * write the joined record from prior1 and prior2 to row_buf
   * @param data1     joined data of prior1
   * @param data1     joined data of prior2
   */  
  void writeRow(void* data1, void* data2);
public:
  /**
   * constructor.
   * @param p1        prior operator 1
   * @param p2        prior operator 2
   */
  Join(Operator *p1, Operator *p2);   
  /**
   * init join operator
   * @retval true     success
   * @retval false    failure
   */ 
  virtual bool open() {return prior->open(); }
  /**
   * get a row of result, return prior getNext() default
   * @retval true     success
   * @retval false    failure
   */
  virtual bool getNext() {return prior->getNext(); }
  /**
   * release resource and close, return prior close() default
   * @retval true     success
   * @retval false    failure
   */
  virtual bool close()  {return prior->close(); }
  /**
   * print the message of join
   * @param indent    indent while printing join operator
   */
  virtual void explain(int indent);
  /**
   * destructor.   
   */
  virtual ~Join();
};

/** definition of hashjoin.  */
class HashJoin : public Join {
private:
  ResultTable *tempT;     /**< temp table to store records of prior2  */
  HashIndex *idx;         /**< hash index we build to implement hashjoin  */
  HashInfo hf;            /**< hash info for lookup */
public:
  /**
   * constructor.
   * @param p1        prior operator 1
   * @param p2        prior operator 2
   * @param JConds    join conditions base on prior and prior2
   */
  HashJoin(Operator *p1, Operator *p2, OpConditions &JConds);

  /**
   * init and alloc resources:
   * call open() of prior and prior2 to init;
   * call getNext() of prior2 and store all the result into result tablel tempT;
   * build hash index based on colrank2 of join conditions;
   * set keyVal so that its elements point to corresponding keys in prior1.
   * @retval true     success
   * @retval false    failure
   */ 
  bool open();

  /**
   * get result of prior and write joined row to row_buf:
   * it firstly judges whether a record's corresponding row in prior2 has been totally found;
   * if not yet, use lookup() to continue to look up;
   * else get a new record of prior and use set_ls() to reset hashinfo and then continue to look up.
   * @retval true     success
   * @retval false    failure
   */
  bool getNext();

  /**
   * release resources like index and temp table
   * @retval true     success
   * @retval false    failure
   */
  bool close();

  /**
   * print the message of join
   * @param indent    indent while printing join operator
   */
  void explain(int indent);
};

/** definition of indexjoin.  */
class IndexJoin : public Join {
private:
  Index *idx;         /**< index of prior2 */
public:

  /**
   * constructor.
   * @param pOuter        prior operator 1
   * @param pInner        prior operator 2 with index, which must be a index scan operator.
   * @param JoinConds     join conditions between prior and prior2
   * @param index         index of prior2's input table
   */
  IndexJoin(Operator *pOuter, Operator *pInner, OpConditions &JoinConds, Index* index);

  /**
   * init and alloc resources:
   * set keyOffset and keyVal to prepare for set_ls();
   * call open() of prior  to init.
   * @retval true     success
   * @retval false    failure
   */ 
  bool open();

  /**
   * write final result to row_buf.
   * @retval true     success
   * @retval false    failure
   */ 
  bool getNext();

  /**
   * release resources and close
   * @retval true     success
   * @retval false    failure
   */
  bool close();

  /**
   * print the message of join
   * @param indent    indent while printing join operator
   */
  void explain(int indent);
};

/** definition of aggrerate info.  */
struct AggrInfo_t{
  int64_t colrank;              /**< column rank of this aggrerate */
  BasicType* dtype;             /**< column data type of this aggrerate */
  AggrerateMethod AggrMethod;   /**< method of this aggrerate */
};

/** definition of groupby, aggrerate and having.  */
class GroupbyAggr : public Operator{
protected:
  ColRanks groupbyRanks;            /**< column ranks of groupby */
  vector<AggrInfo_t> AggrInfo;      /**< aggrerate information */
  ResultTable *tempT;               /**< temp table to store result of Groupby and Aggrerate */
  HashIndex * idx;                  /**< hash index we build in groupby */
  vector<BasicType*>dest_types;     /**< type vector of temp table */
  OpConditions inConds;             /**< conditions of having */
  int64_t line;                     /**< lines of result has been processed */
  vector<AggrerateMethod> aggrcols; /**< aggrinfo of all cols, for higher operators*/

  /**
   * update aggregate information for specific row of result table tempT
   * @param dest         pointer to a exsiting row(will be updated) of result table tempT
   * @param src          pointer to the source record update to tempT
   */ 
  void do_aggr_acc(void* dest, void* src);
  /**
   *  init a aggregate
   *  @param dest         pointer to a new row of result table tempT
   *  @param src          pointer to the source record writen to tempT
   */
  void do_aggr_init(void* dest, void* src);
public:
  /**
   * constructor.
   * @param prior         prior operator
   * @param gbranks       ranks of columns of groupby
   * @param aggrinfo      aggrerate information
   */
  GroupbyAggr(Operator* prior, ColRanks &gbranks, vector<AggrInfo_t>&aggrinfo);

  /**
   * init and alloc resources:
   * build a temp result table and set up the format;
   * judge groupby exists or not,
   * if exists, build hashindex based on group columns, then call
   *   getnext() of prior. if this record repeats, just update the
   *   intermediate results, else init intermediate results and 
   *   insert this record.
   * else just init intermediate results, then continuously call
   *   getnext() of prior and update intermediate results.
   * @retval true     success
   * @retval false    failure
   */ 
  bool open();

  /**
   * get a row from result and do filter(having);
   * then write it to row_buf.
   * @retval true     success
   * @retval false    failure
   */ 
  bool getNext();

  /**
   * release resources like result table and close
   * @retval true     success
   * @retval false    failure
   */
  bool close();
  
  /**
   * print the message of groupby, aggregate, having
   * @param indent    indent while printing
   */
  void explain(int indent);

  /**
   * get column rank of a column in the result table given RequestColumn
   * @param r         input RequestColumn
   * @retval >=0      success
   * @retval -1       failure
   */ 
  int64_t getColRank(RequestColumn& r);

  /**
   * set conditions
   * @param conds     conditions of having
   */ 
  void setInConds(OpConditions &conds);

  /**
   * get aggregate infos of all cols
   * @retval vector of AggrerateMethod
   */ 
  vector<AggrerateMethod>& getAggrCols();
  
  /**
   * destructor.   
   */
  ~GroupbyAggr();
};


/** definition of orderby.  */
class Orderby : public Operator{
private:
  ColRanks cols;                /**< column ranks of orderby */
  vector<void*> q;              /**< vector that stores rows to order */
  ResultTable *tempT;           /**< temp result table to store result of prior */
  size_t line;                  /**< lines of result has been processed */
  vector<AggrerateMethod> AM;   /**< aggregate methods, used for explain*/
public:

  /**
   * constructor.
   * @param prior         prior operator
   * @param cols          ranks of columns of orderby
   * @param AM            aggregate methods
   */
  Orderby(Operator* prior, ColRanks &cols,vector<AggrerateMethod>& AM);

  /**
   * init and alloc resources:
   * build a temp result and push pointer of each row into vector q;
   * sort the pointers in q according to specific cols.
   * @retval true         success
   * @retval false        failure
   */
  bool open();

  /**
   * get a row from result through pointers in q(q has been sorted);
   * then write it to row_buf.
   * @retval true     success
   * @retval false    failure
   */ 
  bool getNext();

  /**
   * release resources like result table and close
   * @retval true     success
   * @retval false    failure
   */
  bool close();
  
  /**
   * print the message of orderby
   * @param indent    indent while printing orderby operator
   */
  void explain(int indent);
  /**
   * destructor.   
   */
  ~Orderby();
};


/** definition of class executor.  */
class Executor {
  private:
    SelectQuery *current_query;  /**< selectquery to iterately execute */

    /** generate a query plan and return the top op of op tree.
     * @param  query to execute.
     * @retval the top op of the generated op tree.
     */
    Operator* planner(SelectQuery *query); 
  public:
    /**
     * exec function.
     * @param  query to execute, if NULL, execute query at last time 
     * @param result table generated by an execution, store result in pattern defined by the result table
     * @retval >0  number of result rows stored in result
     * @retval <=0 no more result
     */
    virtual int exec(SelectQuery *query, ResultTable *result);
    //--------------------------------------
    /**
     * close function.
     * @param None
     * @retval ==0 succeed to close
     * @retval !=0 fail to close
     */
    virtual int close();
};




#endif
