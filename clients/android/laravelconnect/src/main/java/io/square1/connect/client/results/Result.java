package io.square1.connect.client.results;

/**
 * Created by roberto on 10/05/2017.
 */

public abstract class Result {


    private ResultError mError;

    public Result(){
        mError = null;
    }

    public boolean isSuccessful(){
        return mError == null;
    }

    void setError(ResultError error){
        mError = error;
    }

    public abstract Object getData();

}
