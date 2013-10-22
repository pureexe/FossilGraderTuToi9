#ifndef DB_INTERFACE_H_INCLUDED
#define DB_INTERFACE_H_INCLUDED

#ifdef ON_WINDOWS
#include <windows.h>
#endif

#include <stdio.h>
#include <mysql/mysql.h>

#define SUBSTATUS_UNDEFINED   0
#define SUBSTATUS_INQUEUE     1
#define SUBSTATUS_GRADING     2
#define SUBSTATUS_ACCEPTED    3
#define SUBSTATUS_REJECTED    4

typedef MYSQL DB;

DB *connect_db(char* dbname=NULL, char* username=NULL, char* password=NULL, char* ip=NULL);
void close_db(DB *myData);
bool saveprog_from_db(DB *myData,
		      char *user_id, char *prob_id, int sub_num, char *fname);

int findmaxsubnum(DB *myData, char *user_id, char *prob_id);

int getsubstatus(DB *db, char *user_id, char *prob_id);
int getsubstatus(char *user_id, char *prob_id);

void setsubstatus(DB *db, char *user_id, char *prob_id, int status, int score, char *msg, char *host_index, char *compiling);
void setsubstatus(char *user_id, char *prob_id, int status, int score, char *msg);

void fetchqueue(DB *db, char *user_id, char *prob_id, int *sub_num, char *os);

void savecompilermsg(DB *db, char *user_id, char *prob_id, char *msg);

void set_db_config(char* dbname, char* username, char* password, char* ip);

#endif
