#ifndef EXECUTE_H_INCLUDED
#define EXECUTE_H_INCLUDED

#define EXE_RESULT_OK       0
#define EXE_RESULT_TIMEOUT  1
#define EXE_RESULT_MEMORY   2

#ifdef __cplusplus
extern "C" {
#endif

int execute(char *exname, char *inname, char *outname, double t, int max_mem=0);

#ifdef __cplusplus
}
#endif

#endif
