#include "evaluate.h"
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include "execute.h"
#include "db_interface.h"

#ifdef MSVC
// strange commands...

#include <direct.h>
#define chdir						_chdir

// all the system command for windoze
#define DEFAULT_EVDIR	"ev"
#define COPYINCMD	"copy %s\\%s\\%s\\%d.in test > nul:"
#define COPYSOLCMD      "copy %s\\%s\\%s\\%d.sol test > nul:"
#define MOVEOLDTESTCMD  "copy/y test\\*.* old-test > nul:"
#define DELTESTDIR      "del/q test\\*.*"
#define TESTRES_CREATDIR	"mkdir test-res\\%s\\%s > nul:"
#define TESTRES_CPOUTPUT	"copy/y test\\*.out test-res\\%s\\%s > nul:"
#define TESTRES_COMPMSG		"copy/y test\\compile.msg test-res\\%s\\%s > nul:"

#else

#define DEFAULT_EVDIR "ev"

#endif

/*
  Utilities
  ---------
*/

char copybuff[10000];

void copyfile(char* sfname, char* dfname)
{
    FILE* fin;
    FILE* fout;

    if(strcmp(sfname,dfname)==0)
        return;

    fin=fopen(sfname,"r");
    if(fin==NULL)
        return;

    fout=fopen(dfname,"w");
    if(fout==NULL)
    {
        fclose(fin);
        return;
    }

    while(fgets(copybuff,9999,fin)!=NULL)
        fprintf(fout,"%s",copybuff);
    fclose(fin);
    fclose(fout);
}

void copysmallfile(char* sfname, char* dfname)
{
    FILE* fin;
    FILE* fout;

    if(strcmp(sfname,dfname)==0)
        return;

    fin=fopen(sfname,"r");
    if(fin==NULL)
        return;

    fout=fopen(dfname,"w");
    if(fout==NULL)
    {
        fclose(fin);
        return;
    }

    int count=0;
    char line[100];
    while((count<100) && (fgets(line,99,fin)!=NULL))
    {
        fprintf(fout,"%s",line);
        count++;
    }
    fclose(fin);
    fclose(fout);
}

bool iffileexist(char* fname)
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

bool iffullpath(char* path)
{
    return (path[0]=='\\') || (path[1]==':');
}

/*
  Compiler configurations
  -----------------------
*/

static int compiler_count = 0;
compiler_config compiler_configs[MAX_COMPILERS];

void add_compiler(compiler_config config)
{
    compiler_configs[compiler_count].name = strdup(config.name);
    compiler_configs[compiler_count].c_compilation_command =
        strdup(config.c_compilation_command);
    compiler_configs[compiler_count].cpp_compilation_command =
        strdup(config.cpp_compilation_command);
    compiler_count++;
}

evaluator::evaluator(DB *mydb, char* evdir, char* dbname)
{
    prob_id = 0;
    ev_name = 0;
    infname = 0;
    outfname = 0;

    casecount = 0;
    fullscore = 0;
    db = mydb;
    language = EV_LANG_NONE;

    db_name = strdup(dbname);
    if(evdir!=0)
        ev_dir = strdup(evdir);
    else
        ev_dir = strdup(DEFAULT_EVDIR);
}

evaluator::~evaluator()
{
    if(prob_id!=0)
        free(prob_id);
    if(ev_name!=0)
        free(ev_name);
    if(infname!=0)
        free(infname);
    if(outfname!=0)
        free(outfname);
    if(ev_dir!=0)
        free(ev_dir);
}

void evaluator::readconf(char* pname)
{
    char confname[200];
    char line[100];

    char topic[200];
    //char *value = new char[100];
    char value[200];

    if(prob_id!=0)
        free(prob_id);
    prob_id = strdup(pname);
    sprintf(confname,"%s/%s/%s/config/conf",ev_dir,db_name,pname);
    FILE *fp = fopen(confname,"r");
    if(fp!=NULL)
    {
        int index = -1;
        casecount = fullscore = 0;
        timelimit = 1.0;
        memorylimit = 0;

        while(fgets(line,100,fp)!=NULL)
        {

            sscanf(line,"%s %s",topic,value);
            if(strcmp(topic,"cases:")==0)
                sscanf(value,"%d",&casecount);
            else if(strcmp(topic,"total:")==0)
                sscanf(value,"%d",&fullscore);
            else if(strcmp(topic,"score:")==0)
                sscanf(value,"%d",&score_case[++index]);
            //    for(int c=-1, n, ; sscanf(value, "%d%*c%n", &score_case[++c], &n) ; value=&value[n] );
            else if(strcmp(topic,"evaluator:")==0)
            {
                if(ev_name!=NULL)
                    free(ev_name);
                ev_name = strdup(value);
            }
            else if(strcmp(topic,"input:")==0)
            {
                if(infname!=NULL)
                    free(infname);
                infname = strdup(value);
            }
            else if(strcmp(topic,"output:")==0)
            {
                if(outfname!=NULL)
                    free(outfname);
                outfname = strdup(value);
            }
            else if(strcmp(topic,"timelimit:")==0)
                sscanf(value,"%lf",&timelimit);
            else if(strcmp(topic,"memorylimit:")==0){
                sscanf(value,"%d",&memorylimit);
                memorylimit *= 1024*1024;
            }
        }
        if(fullscore==0)
            fullscore=casecount;

        //for( int i=0 ; i<casecount ; ++i ){
        //    score_case[i] = fullscore/casecount;
        //}

        fclose(fp);
    }
    else
    {
        printf("Warning: config file for problem %s not found.\n");
        casecount = fullscore = 0;
    }

    //delete[] value;
}

int evaluator::verify(char *vname, int c, char *msg)
{
    char inname[20];
    char outname[20];
    char solname[20];
    char scorename[20];
    char cmd[200];
    char line[100];
    FILE *sfp;
    float score;
    int point;

    sprintf(inname,"%d.in",c);
    sprintf(outname,"%d.out",c);
    sprintf(solname,"%d.sol",c);
    sprintf(scorename,"%d.score",c);

    sprintf(cmd,"%s %s %s %s",vname,inname,outname,solname);

    if(!iffileexist(vname))
        printf("comparator not found, looking for: %s\n",vname);
    else
        execute(cmd,"-",scorename,100);

    sfp = fopen(scorename,"r");
    score=0;
    *msg = '\0';
    if(sfp!=NULL)
    {
        fgets(line,99,sfp);
        sscanf(line,"%f",&score);
        point = (int)(score_case[c-1]*score/100);
        sprintf(line,"%d",point);
        //if(fgets(line,99,sfp)!=NULL)
            strcpy(msg,line);
        fclose(sfp);
    }

    return point;
}

int evaluator::test(int c, char* msg)
{
    //This function is executed inside "./test/"
    char inname[10];
    char outname[10];
    char solname[10];
    char evfullname[20];
    int exres;

    sprintf(inname,"%d.in",c);
    sprintf(outname,"%d.out",c);
    sprintf(solname,"%d.sol",c);

    if(infname!=0)
        copyfile(inname,infname);

    if(outfname==0){
        exres = execute(prob_id, inname, outname, timelimit, memorylimit);
    }
    else
    {
        exres = execute(prob_id, inname, 0, timelimit, memorylimit);
        copyfile(outfname,outname);
    }

    if(exres==EXE_RESULT_OK)
    {
        //  fprintf(stderr,"verifying...\n");
        if(iffullpath(ev_dir))
            sprintf(evfullname,"%s/%s/%s/%s",ev_dir,db_name,prob_id,ev_name);
        else
            sprintf(evfullname,"../%s/%s/%s/%s",ev_dir,db_name,prob_id,ev_name);
        //   printf("%s\n",evfullname);
        return verify(evfullname,c,msg);
    }
    else if(exres==EXE_RESULT_TIMEOUT)
    {
        strcpy(msg,"T");
        return 0;
    }
    else if(exres==EXE_RESULT_MEMORY)
    {
        strcpy(msg,"x");
        return 0;
    }
}

void evaluator::copytestcase(int c)
{
    char cmd[200];

    sprintf(cmd,COPYINCMD,ev_dir,db_name,prob_id,c);
    //printf("[%s]\n",cmd);
    system(cmd);
    sprintf(cmd,COPYSOLCMD,ev_dir,db_name,prob_id,c);
    //printf("[%s]\n",cmd);
    system(cmd);
}

char* evaluator::read_compiler_message(char* user_id, int compiler_index)
{
    compiler_index = 1;

    DB* myData;
    char fname[100];
    char msg[1001];
    int msgsize;
    FILE *fp;

    if(db!=0)
        myData = db;
    else
        myData = connect_db();
    sprintf(fname,"test-res/%s/%s/%s/%d/compile.msg",db_name,user_id,prob_id,compiler_index);
    if((fp = fopen(fname,"r"))!=NULL)
    {
        msgsize = fread(msg,1,999,fp);
        msg[msgsize]='\0';
        fclose(fp);
    }
    else
    {
        msgsize = 0;
        msg[0]='\0';
    }
    return strdup(msg);
}

void evaluator::savemessage(char* user_id, int c)
{
    DB *myData;
    char temp_msg[1100];
    char msg[1100*MAX_COMPILERS];

    if(db!=0)
        myData = db;
    else
        myData = connect_db();

    msg[0] = '\0';
    if( c != EV_COMPILER_ERROR )
    //for(int c=0; c<compiler_count; c++)
    {
        char* one_msg = read_compiler_message(user_id,c);
        if(one_msg!=0)
        {
            sprintf(temp_msg,"=================== compiler: %s ==================\n%s",
                    compiler_configs[c].name,one_msg);
            strcat(msg,temp_msg);
        }
        free(one_msg);
    }
    else{
        strcat(msg,"no compiler select");
    }

    savecompilermsg(myData,user_id,prob_id,msg);
    if(db==0)
        close_db(myData);
}

void evaluator::copyoutput(char *user_id, int compiler_index)
{
    compiler_index = 1;

    char dir[100];
    char finname[100];
    char foutname[100];

    sprintf(dir,"test-res/%s",db_name);
    mkdir(dir);
    sprintf(dir,"test-res/%s/%s",db_name,user_id);
    mkdir(dir);
    sprintf(dir,"test-res/%s/%s/%s",db_name,user_id,prob_id);
    mkdir(dir);
    sprintf(dir,"test-res/%s/%s/%s/%d",db_name,user_id,prob_id,compiler_index);
    mkdir(dir);

    for(int i=0; i<casecount; i++)
    {
        sprintf(finname,"test/%d.out",i+1);
        sprintf(foutname,"test-res/%s/%s/%s/%d/%d.out", db_name,user_id,prob_id,compiler_index,i+1);
        copysmallfile(finname,foutname);
    }

    //copy compile message
    sprintf(foutname,"test-res/%s/%s/%s/%d/compile.msg", db_name,user_id,prob_id,compiler_index);
    copyfile("test/compile.msg",foutname);

    //copy source code
    sprintf(finname,"test/%s.c",prob_id);
    sprintf(foutname,"test-res/%s/%s/%s/%s.c",db_name,user_id,prob_id,prob_id);
    copyfile(finname,foutname);
}

void evaluator::cleartestdir()
{
//  system(MOVEOLDTESTCMD);
    system(DELTESTDIR);
}

void evaluator::fetchsource(char* user_id, int sub_num, char *fname)
{
    DB *myData;

    if(db==0)
        myData = connect_db();
    else
        myData = db;

    saveprog_from_db(myData,user_id,prob_id,sub_num,fname);

    if(db==0)
        close_db(myData);
}

char *strupper(char *st)
{
    char *p = st;

    while(*p!=0)
    {
        if(((*p)>='a') && ((*p)<='z'))
            *p+=('A'-'a');
        p++;
    }
    return st;
}

int evaluator::getcompiler(char *fname)
{
    FILE *fp=fopen(fname, "r");
    char line[100];
    int lcount;

    if(fp==NULL)
        return EV_COMPILER_ERROR;

    lcount=0;
    while((lcount<10) && (fgets(line,99,fp)!=NULL))
    {
        lcount++;
        strupper(line);
        // TODO: this is complete hack.  should fix it.

        if((strstr(line,"COMPILER:")!=NULL) || (strstr(line,"COMPILER :")!=NULL))
        {
            if(strstr(line,"WDC")!=NULL)
            {
                fclose(fp);
                return EV_COMPILER_WINDOWS_DEVC;
            }
            else if(strstr(line,"WCB")!=NULL)
            {
                fclose(fp);
                return EV_COMPILER_WINDOWS_CODEBLOCK;
            }
            else if(strstr(line,"LINUX")!=NULL)
            {
                fclose(fp);
                return EV_COMPILER_LINUX;
            }

        }

    }
    fclose(fp);
    return EV_COMPILER_ERROR;
}

int evaluator::getlanguage(char *fname)
{
    FILE *fp=fopen(fname, "r");
    char line[100];
    int lcount;

    if(fp==NULL)
        return -1;

    lcount=0;
    while((lcount<10) && (fgets(line,99,fp)!=NULL))
    {
        lcount++;
        strupper(line);
        // TODO: this is complete hack.  should fix it.
        if((strstr(line,"LANG:")!=NULL) || (strstr(line,"LANG :")!=NULL))
        {
            if(strstr(line,"C++")!=NULL)
            {
                fclose(fp);
                return EV_LANG_CPP;
            }
            else if(strstr(line,"C")!=NULL)
            {
                fclose(fp);
                return EV_LANG_C;
            }
        }
    }
    fclose(fp);
    return EV_LANG_ERROR;
}

void writecompilemsg(char *st)
{
    FILE *fp=fopen("test/compile.msg","w");

    if(fp!=NULL)
    {
        fprintf(fp,"%s",st);
        fclose(fp);
    }
}

void appendcompilemsg(char *st)
{
    FILE *fp=fopen("test/compile.msg","a");

    if(fp!=NULL)
    {
        fprintf(fp,"%s",st);
        fclose(fp);
    }
}

bool evaluator::fetchandcompile(char *user_id, int sub_num,
                                compiler_config comp_config)
{
    char sourcename[100];
    char rel_sourcename[100];
    char cmd[100];
    char exname[100];
    int lang;

    sprintf(sourcename,"test/%s.c",prob_id);
    sprintf(rel_sourcename,"%s.c",prob_id);
    sprintf(exname,"%s.exe",prob_id);

    //fetchsource(user_id,sub_num,sourcename);
    if(language==EV_LANG_NONE)
        lang = getlanguage(sourcename);
    else
        lang = language;

    if(lang==EV_LANG_ERROR)
    {
        writecompilemsg("Cannot determine language used. Check your program header.\n");
        return false;
    }
    else
    {
        if(chdir("test")!=0)
            printf("Error: 'test' directory not found.  Please create it.\n");
        if(lang==EV_LANG_C)
            sprintf(cmd,comp_config.c_compilation_command,
                    prob_id,prob_id,prob_id);
        else
            sprintf(cmd,comp_config.cpp_compilation_command,
                    prob_id,prob_id,prob_id, comp_config.cpp_compilation_command);
        system(cmd);
        chdir("..");
        sprintf(exname,"test\\%s.exe",prob_id);
        if(iffileexist(exname))
        {
            appendcompilemsg("\n---------------------------\nCompiled successfully.\n");
            return true;
        }
        else
        {
            appendcompilemsg("\n---------------------------\nError compiling source program.\n");
            return false;
        }
    }
}

int evaluator::evaluate(char* user_id, int sub_num, char *mlog)
{
    int score = 0;
    int best_score = 0;
    char msg[100];
    char msg_with_name[200];
    char thismsg[100];

    // clear log
    if(mlog != 0)
        mlog[0] = '\0';

    //for(int c=0; c<compiler_count; c++)

    char sourcename[100];
    int c;
    sprintf(sourcename,"test/%s.c",prob_id);
    fetchsource(user_id,sub_num,sourcename);

    if( (c=getcompiler( sourcename )) != EV_COMPILER_ERROR)
    {
        if(fetchandcompile(user_id,sub_num,compiler_configs[c]))
        {
            score = 0;
            msg[0]='\0';
            printf("%s:",compiler_configs[c].name);
            for(int i=0; i<casecount; i++)
            {

                // to make life not so boring...
                printf("[%d]",i+1);
                fflush(stdout);

                copytestcase(i+1);

                chdir("test");
                score += test(i+1,thismsg);
                chdir("..");

                if( i>0 )
                    strcat(msg,"-");
                strcat(msg,thismsg);
            }
            printf("\n");
        }
        else{
            strcpy(msg,"compile error");
        }
        if(score>best_score)
            best_score = score;

        copyoutput(user_id,c);
        cleartestdir();

        //if(compiler_count!=1)
            //sprintf(msg_with_name,"%s[%s]",compiler_configs[c].name,msg);
        //else

            sprintf(msg_with_name,"[%s]",msg);

        if(mlog!=0)
        {
            //if(c==0)
            //    strcpy(mlog,msg_with_name);
            //else
            //{
            //    strcat(mlog," | ");
                strcat(mlog,msg_with_name);
            //}
        }
    }
    else{
        //printf("=> getcompiler => false\n");

    }

    if(KEEP_COMPILE_MSG)
        savemessage(user_id, c);

    return best_score;
}

/*
//  printf("3\n");
if(s.estatus == submission_db::GRADING) {
//    printf("prob wait in queue\n");

int version = s.sub_count;

if((conf!=0) && (conf->chkdeadline)) {
version = logfile::findversion(id, u->getsect(), prob_id,
conf->hr, conf->min,
conf->d, conf->m, conf->y);
if(version!=s.sub_count)
				printf("(%d/%d)\n", version,s.sub_count);
      if(version>s.sub_count)
				version=s.sub_count;
    }

    if((version!=0) && (copyandcompile(id,u->getsect(),version,s.lang))) {
      char thismsg[100];

      for(int i=0; i<casecount; i++) {
				copytestcase(i+1);

				chdir("test");
				score += test(i+1,thismsg);
				chdir("..");

				strcat(evmsg,thismsg);
      }
    } else {
      score = 0;
      strcpy(evmsg,"-\ncompile error");
    }

    savemessage(id,u->getsect(),evmsg);

    submission_db* db = new submission_db(id,u->getsect());
    if(score==fullscore)
      s.estatus=submission_db::PASSED;
    else
      s.estatus=submission_db::FAILED;
    db->setsubstatus(probnum,s);
    delete db;
  } else {
    strcpy(evmsg,"not submitted");
    score = -1;
  }
  delete u;

  cleartestdir();

  if(mlog)
    strcpy(mlog,evmsg);
  return score;
*/
