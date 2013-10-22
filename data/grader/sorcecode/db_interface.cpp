#include <windows.h>
#include <stdio.h>
#include <string.h>
#include <stdlib.h>

#include <mysql/mysql.h>
#include "db_interface.h"

int getsubstatus(char *user_id, char *prob_id)
{
    return getsubstatus(0, user_id, prob_id);
}

int getsubstatus(DB *db, char *user_id, char *prob_id)
{
    DB *myData;
    char query[100];
    MYSQL_RES *res;
    MYSQL_ROW row;

    int status;

    if(db==0)
        myData = connect_db();
    else
        myData = db;

    mysql_query(myData,"LOCK TABLES grd_status READ");

    sprintf(query,"SELECT res_id FROM grd_status WHERE user_id=\"%s\" and prob_id=\"%s\"",
            user_id, prob_id);

    if(!mysql_query(myData,query))
    {
        res = mysql_store_result(myData);
        if(mysql_num_rows(res)==1)
        {
            row = mysql_fetch_row(res);
            sscanf(row[0],"%d",&status);
        }
        else
            status = SUBSTATUS_UNDEFINED;
        mysql_free_result(res);
    }
    else
        status = SUBSTATUS_UNDEFINED;
    mysql_query(myData,"UNLOCK TABLES");

    if(db==0)
        close_db(myData);

    return status;
}

void setsubstatus(char *user_id, char *prob_id, int status, int score, char *msg)
{
    setsubstatus(0, user_id, prob_id, status, score, msg, "", "");
}

void setsubstatus(DB *db, char *user_id, char *prob_id, int status, int score,  char *msg, char *host_index, char *compiling)
{
    DB *myData;
    char query[1000];
    MYSQL_RES *res;

    if(db==0)
        myData = connect_db();
    else
        myData = db;

    mysql_query(myData,"LOCK TABLES grd_status WRITE");

    sprintf(query,"SELECT res_id FROM grd_status WHERE user_id=\"%s\" and prob_id=\"%s\"",
            user_id, prob_id);

    if(!mysql_query(myData,query))
    {
        res = mysql_store_result(myData);
        unsigned long numrow = mysql_num_rows(res);
        mysql_free_result(res);

        if(numrow==1)
            sprintf(query,"UPDATE grd_status SET res_id=%d, score=%d, grading_msg=\"%s\", host_index=\"%s\", compiling=\"%s\" " \
                    "WHERE user_id=\"%s\" and prob_id=\"%s\"",
                    status, score, msg, host_index, compiling, user_id, prob_id);
        else
            sprintf(query,"INSERT INTO grd_status (user_id, prob_id, res_id, score, grading_msg, host_index, compiling) VALUES " \
                    "(\"%s\",\"%s\",%d,%d,\"%s\",\"%s\",\"%s\")", user_id, prob_id, status, score, msg, host_index, compiling);
        mysql_query(myData,query);
    }

    mysql_query(myData,"UNLOCK TABLES");

    if(db==0)
        close_db(myData);
}

void savecompilermsg(DB *db, char *user_id, char *prob_id, char *msg)
{
    const int MSG_BUF_SIZE = 5000;

    char msg_buffer[MSG_BUF_SIZE+1];
    char msg_enbuffer[MSG_BUF_SIZE*2+3];

    char query[MSG_BUF_SIZE*2+3+100];

    MYSQL_RES *res;

    sprintf(query,"SELECT * FROM grd_status WHERE user_id=\"%s\" and prob_id=\"%s\"",
            user_id, prob_id);

    if(!mysql_query(db,query))
    {
        res = mysql_store_result(db);
        if(mysql_num_rows(res)==1)
        {
            mysql_free_result(res);

            strncpy(msg_buffer,msg,MSG_BUF_SIZE);
            msg_buffer[MSG_BUF_SIZE]='\0';
            mysql_real_escape_string(db,msg_enbuffer,msg_buffer,strlen(msg_buffer));

            sprintf(query,"UPDATE grd_status SET compiler_msg=\"%s\" "	\
                    " WHERE user_id=\"%s\" AND prob_id=\"%s\"",
                    msg_enbuffer, user_id, prob_id);
            //			printf("QUERY: %s",query);
            mysql_query(db,query);
        }
        else
            mysql_free_result(res);
    }
}

void fetchqueue(DB *db, char *user_id, char *prob_id, int *sub_num, char *os)
{
    char query[100];
    MYSQL_RES *res;

    mysql_query(db,"LOCK TABLES grd_queue WRITE");

    if( strcmp(os,"windows")==0 )
        sprintf(query,"SELECT MIN(q_id) AS q_id FROM grd_queue WHERE compiler=\"WCB\" OR compiler=\"WDC\"");
    else
        sprintf(query,"SELECT MIN(q_id) AS q_id FROM grd_queue WHERE compiler=\"LINUX\"");

    if(!mysql_query(db, query))
    {
        res = mysql_store_result(db);
        if(mysql_num_rows(res)==1)
        {

            MYSQL_ROW row = mysql_fetch_row(res);
            int qid;

            if(row[0]!=NULL)
            {
                sscanf(row[0],"%d",&qid);

                mysql_free_result(res);

                sprintf(query,"SELECT user_id,prob_id,sub_num FROM grd_queue "	\
                        "WHERE q_id=%d",qid);
                mysql_query(db,query);

                res = mysql_store_result(db);
                row = mysql_fetch_row(res);

                strcpy(user_id,row[0]);
                strcpy(prob_id,row[1]);
                sscanf(row[2],"%d",sub_num);

                mysql_free_result(res);

                sprintf(query,"DELETE FROM grd_queue WHERE q_id=%d",qid);
                mysql_query(db,query);
            }
            else
            {
                *user_id = '\0';
                *prob_id = '\0';
                *sub_num = 0;
                mysql_free_result(res);
            }
        }
        else
        {
            *user_id = '\0';
            *prob_id = '\0';
            *sub_num = 0;
            mysql_free_result(res);
        }
    }
    else
    {
        printf("%s\n",mysql_error(db));
        *user_id = '\0';
        *prob_id = '\0';
        *sub_num = 0;

        db = connect_db();
    }

    mysql_query(db,"UNLOCK TABLES");
}

int findmaxsubnum(DB *myData, char *user_id, char *prob_id)
{
    char query[200];

    sprintf(query,"SELECT MAX(sub_num) AS sub_num FROM submission WHERE user_id=\"%s\"" \
            " and prob_id=\"%s\"",user_id,prob_id);
    //	printf("%s\n",query);
    if(!mysql_query(myData,query))
    {

        MYSQL_RES *res = mysql_store_result(myData);
        MYSQL_ROW row;
        int num;

        if(mysql_num_rows(res)!=0)
        {
            row = mysql_fetch_row(res);
            if(row[0]!=NULL)
                sscanf(row[0],"%d",&num);
            else
                num=0;
        }
        else
        {
            num=0;
        }

        mysql_free_result(res);
        return num;
    }
    else
        return 0;
}

bool saveprog_from_db(DB *myData,
                      char *user_id, char *prob_id, int sub_num, char *fname)
{
    char query[200];
    MYSQL_RES *res;
    MYSQL_FIELD	*fd;
    MYSQL_ROW row;

    sprintf(query,"select * from submission where user_id=\"%s\" and prob_id=\"%s\" and "	\
            "sub_num = %d",
            user_id, prob_id, sub_num);

    if(!mysql_query(myData,query))
    {
        res = mysql_store_result(myData);
        if(mysql_num_rows(res)!=0)
        {
            int codefd;

            for(codefd = 0; fd = mysql_fetch_field(res); codefd++)
                if(strcmp(fd->name,"code")==0)
                    break;

            row = mysql_fetch_row(res);

            FILE *fp = fopen(fname,"w");
            if(fp!=NULL)
            {
                fprintf(fp,"%s",row[codefd]);
                fclose(fp);
                return true;
            }
            else
                return false;

            mysql_free_result(res);
        }
        else
        {
            mysql_free_result(res);
            return false;
        }
    }
    else
        return false;
}

static char *db_username="root";
static char *db_password="newpwd";
static char *db_dbname  ="db";
static char *db_ip      ="localhost";

void set_db_config(char* dbname, char* username, char* password, char *ip)
{
    db_username=strdup(username);
    db_password=strdup(password);
    db_dbname  =strdup(dbname);
    db_ip      =strdup(ip);
}

DB *connect_db(char* dbname, char* username, char* password, char *ip)
{
    MYSQL *myData ;

    /*
      if((myData = mysql_init((MYSQL*) 0)) &&
      mysql_real_connect(myData, NULL, "jittat", "",
      NULL, MYSQL_PORT, NULL, 0)) {
    */

    // initialize passwd
    if(username==NULL)
        username=db_username;
    if(password==NULL)
        password=db_password;
    if(dbname==NULL)
        dbname=db_dbname;
    if(ip==NULL)
        ip=db_ip;

    printf("database: %s %s\n",dbname, ip);

    if((myData = mysql_init((MYSQL*) 0)) && mysql_real_connect(myData, ip, username, password,
                               NULL, MYSQL_PORT, NULL, 0))
    {
        if (mysql_select_db(myData, dbname) < 0 )
        {
            printf("Can't select the %s database ioi\n");
            mysql_close( myData ) ;
            return NULL;
        }
    }
    else
    {
        printf("Can't connect to the mysql server (%s/%s) on port %d !\n",
               username, password,MYSQL_PORT);
        mysql_close( myData );

        getchar();
        exit(1);
        return NULL;
    }
    return myData;
}

void close_db(DB *myData)
{
    mysql_close(myData);
}

