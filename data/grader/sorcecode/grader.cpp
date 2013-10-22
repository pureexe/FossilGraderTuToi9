#include<iostream>
using namespace std;

#include "db_interface.h"
#include "evaluate.h"
#include <cstdio>
#include <cstring>

#define CONFIG_FILE  "grader.conf"

//#define CHOOSE_BEST_LANG 1

// configurations
char username[100];
char password[100];
char dbname[100];
char* ev_dir;
char os[100];
char ip[100];
char host_index[100];

char* copy_free_str_arg(char* line)
{
    char* p = line;
    while(((*p)!='\0') && ((*p)!=':'))
        p++;
    if(*p=='\0')
        return strdup("");

    p++;

    char* t = p;
    while((*t)!='\0')
        t++;
    t--;
    while((t>=p) && (((*t)=='\n') || ((*t)=='\r')))
        t--;
    t++;
    *t = '\0';
    return strdup(p);
}

void readconfig(char db[100])
{
    FILE *fp;
    char line[500];
    char cmd[200];
    char val[200];

    username[0]='\0';
    password[0]='\0';
    //dbname[0]='\0';
    ev_dir = 0;
    strcpy(dbname,db);

    compiler_config config;
    int config_count = 0;

    if((fp=fopen(CONFIG_FILE,"r"))!=NULL)
    {
        while(fgets(line,499,fp)!=NULL)
        {
            if(line[0]=='#') // comments
                continue;

            if(sscanf(line,"%s %s",cmd,val)<=1)
                continue;

            if(strcmp(cmd,"username:")==0)
                strcpy(username,val);
            else if(strcmp(cmd,"password:")==0)
                strcpy(password,val);
            //else if(strcmp(cmd,"database:")==0)
            //    strcpy(dbname,val);
            else if(strcmp(cmd,"ev_dir:")==0)
                ev_dir = strdup(val);
            else if(strcmp(cmd,"os:")==0)
                strcpy(os,val);
            else if(strcmp(cmd,"ip:")==0)
                strcpy(ip,val);
            else if(strcmp(cmd,"host_index:")==0)
                strcpy(host_index,val);

            // compiler config
            if(strcmp(cmd,"compiler:")==0)
            {
                if(atoi(val)!=config_count)
                {
                    if(config_count!=0)
                        add_compiler(config);
                    config_count++;
                }
            }
            else if(strcmp(cmd,"compiler-name:")==0)
                config.name = strdup(val);
            else if(strcmp(cmd,"compiler-c-cmd:")==0)
                config.c_compilation_command = copy_free_str_arg(line);
            else if(strcmp(cmd,"compiler-cpp-cmd:")==0)
                config.cpp_compilation_command = copy_free_str_arg(line);
        }
    }
    else
        printf("No open\n");

    set_db_config(dbname,username,password,ip);

    if(config_count!=0)
        add_compiler(config);
}

void grade(DB *db, char *user_id, char *prob_id, int sub_num)
{
    evaluator ev(db, ev_dir, dbname);
    int score;
    char msg[100];

    setsubstatus(db,user_id,prob_id,SUBSTATUS_GRADING,0,"",host_index,"process");
    ev.readconf(prob_id);

#ifndef CHOOSE_BEST_LANG
    score = ev.evaluate(user_id,sub_num,msg);

#else
    char msg1[100], msg2[100];
    int s1, s2;
    ev.forcelanguage(EV_LANG_C);
    s1 = ev.evaluate(user_id,sub_num,msg1);
    printf("In C: %d (%s)\n",s1,msg1);

    ev.forcelanguage(EV_LANG_CPP);
    s2 = ev.evaluate(user_id,sub_num,msg2);
    printf("In C++: %d (%s)\n",s2,msg2);

    if((s1==0) && (s2==0))
    {
        // both zero... report one which compiled
        if(strstr(msg1,"error")==NULL)
            strcpy(msg,msg1);
        else
            strcpy(msg,msg2);
    }
    else if(s1>s2)
    {
        strcpy(msg,msg1);
        score = s1;
    }
    else
    {
        strcpy(msg,msg2);
        score = s2;
    }

#endif
/*
    if(score == ev.getfullscore())
        setsubstatus(db,user_id,prob_id,SUBSTATUS_ACCEPTED,score,msg);
    else
        setsubstatus(db,user_id,prob_id,SUBSTATUS_REJECTED,score,msg);
*/

    if(score == ev.getfullscore())
        setsubstatus(db,user_id,prob_id,SUBSTATUS_ACCEPTED,score,msg,host_index,"finish");
    else
        setsubstatus(db,user_id,prob_id,SUBSTATUS_REJECTED,score,msg,host_index,"finish");

}

void grade(DB *db, char *user_id, char *prob_id)
{
    int snum = findmaxsubnum(db,user_id,prob_id);
    if(snum!=0)
        grade(db, user_id, prob_id, snum);
}

void gradequeue(DB *db)
{
    char user_id[100];
    char prob_id[100];
    int sub_num;

    do
    {
        fetchqueue(db,user_id,prob_id,&sub_num,os);
        if(*user_id!='\0')
        {
            printf("grading: %s/%s/%d\n",user_id,prob_id,sub_num);
            grade(db,user_id,prob_id,sub_num);
        }
    }
    while(*user_id!='\0');
}

static bool iffileexist(char *fname)
{
    FILE *fp = fopen(fname,"r");

    if(fp!=NULL)
    {
        fclose(fp);
        return 1;
    }
    else
        return 0;
}

bool checkexit()
{
    return iffileexist("exit");
}

void stopgrader()
{
    FILE *fp = fopen("exit","w");
    fclose(fp);
    Sleep(2000);
    remove("exit");
}

void gradequeue()
{
    DB *db = connect_db();
    char *moving_icon = "-\\|/";
    int counter = 0;
    while(1)
    {
        gradequeue(db);

        Sleep(1000);
        if(checkexit())
            break;
        printf("%c\r",moving_icon[counter]);
        counter++;
        if(counter>=strlen(moving_icon))
            counter = 0;
    }
    close_db(db);
}

void gradeone(char *user_id, char *prob_id)
{
    DB *db = connect_db();
    grade(db,user_id,prob_id);
    close_db(db);
}

void gradeprob(char *prob_id)
{
    DB *db = connect_db();
    MYSQL_RES *res;
    MYSQL_ROW row;
    int usercount;

    if(!mysql_query(db,"SELECT user_id,name FROM user_info WHERE type='C' OR type='A'"))
    {
        res = mysql_store_result(db);
        usercount = mysql_num_rows(res);
        for(int i=0; i<usercount; i++)
        {
            row = mysql_fetch_row(res);
            printf("grading[%s]: %s\n",prob_id,row[0]);
            grade(db,row[0],prob_id);
        }
        mysql_free_result(res);
    }
    close_db(db);
}

void gradeuser(char *user_id)
{
    printf("sorry: incomplete feature\n");
}

void gradeall()
{
    DB *db = connect_db();
    MYSQL_RES *res;
    MYSQL_ROW row;
    int probcount;
    char **plist;

    // fetch problem lists
    if(!mysql_query(db,"SELECT prob_id FROM prob_info WHERE avail='Y'"))
    {
        res = mysql_store_result(db);
        probcount = mysql_num_rows(res);

        plist = (char **)malloc(sizeof(char *)*probcount);
        for(int i=0; i<probcount; i++)
        {
            row = mysql_fetch_row(res);
            plist[i] = strdup(row[0]);
        }
        mysql_free_result(res);
        close_db(db);

        for(int j=0; j<probcount; j++)
        {
            printf("[%s]\n",plist[j]);
            gradeprob(plist[j]);
            free(plist[j]);
        }

        free(plist);
    }
    else
    {
        close_db(db);
    }
}

/*
parameter:
first argument - command, one of (queue, stop, grade, grade-prob, grade-user, grade-all)
  queue: stands by and grades submission in queue
  stop: stop the current run of another grader in queue-mode
  grade [user_id] [prob_id] : grade recent submission of [prob_id] of [user_id]
  grade-prob [list of prob_id's] : grade submissions of prob of all users
  grade-user [list of user_id's] : grade all submissions of users
  grade-all : grade all submissions of all users

if no argument is given, will work in queue-mode ---
call another grader with stop command to stop the current run.
*/
int main(int argc, char *argv[])
{
    if(argc==1) return 0;

    readconfig(argv[1]);
    printf("hostname: %s\n", host_index);
    if(argc!=2)
    {
        if(strcmp(argv[2],"stop")==0)
            stopgrader();
        else if((strcmp(argv[2],"grade")==0) && (argc==5))
            gradeone(argv[3],argv[4]);
        else if(strcmp(argv[2],"grade-prob")==0)
        {
            for(int i=0; i<argc-3; i++)
                gradeprob(argv[3+i]);
        }
        else if(strcmp(argv[2],"grade-user")==0)
        {
            for(int i=0; i<argc-3; i++)
                gradeuser(argv[3+i]);
        }
        else if(strcmp(argv[2],"grade-all")==0)
            gradeall();
        else if(strcmp(argv[2],"queue")==0)
            gradequeue();
        else
        {
            printf("using: grader [database-name] [subject-name] [queue/stop/grade/grade-prob/grade-user/grade-all] (list...)\n");
        }
    }
    else
        gradequeue();

}
