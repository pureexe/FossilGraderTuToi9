#include <iostream>
#include <algorithm>
#include <fstream>
#include <vector>
#include <string>

#define iswhitespace(x) ((x)==' '||(x)=='\t'||(x)=='\r')

using namespace std;

vector< string > data_sol;
vector< string > data_out;

string line;
int i, point;

int main(int argc, char *argv[]){
    //if(argc!=4){
    //    printf("0");
    //    return 0;
    //}

    ifstream fout("1.out");
    ifstream fsol("1.sol");

    //ifstream fout(argv[2]);
    //ifstream fsol(argv[3]);
    if( !fout || !fsol  ){
        printf("0");
        return 0;
    }
    while( !fsol.eof() ){
        getline(fsol, line, '\n');
        for( i=line.length()-1; i>=0 && iswhitespace(line[i]) ; --i );
        if( i>=0 )
            line = line.substr(0, i+1);
        if( line[0]!='\0' )
            data_sol.push_back( line );
    }
    while( !fout.eof() ){
        getline(fout, line, '\n');
        for( i=line.length()-1; i>=0 && iswhitespace(line[i]) ; --i );
        if( i>=0 )
            line = line.substr(0, i+1);
        if( line[0]!='\0' )
            data_out.push_back( line );
    }
    fsol.close();
    fout.close();

    if( data_sol.size()!=data_out.size() ){
        printf("0");
        return 0;
    }

    sort(data_sol.begin(), data_sol.end());
    sort(data_out.begin(), data_out.end());

    for( i=point=0 ; i<data_sol.size() ; ++i ){
        if(data_sol[i].compare(data_out[i])==0)
            ++point;
    }

    printf("%f", point*100.0/data_sol.size());
    return 0;
}
