package io.square1.connect.client.results;

/**
 * Created by roberto on 05/06/2017.
 */

public class ResultError extends Error {

    public ResultError(Throwable throwable){
        super(throwable.getMessage());
    }
}
