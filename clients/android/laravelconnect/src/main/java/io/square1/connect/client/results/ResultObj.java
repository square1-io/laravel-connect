package io.square1.connect.client.results;

import io.square1.connect.model.BaseModel;


/**
 * Created by roberto on 02/06/2017.
 */

public class ResultObj<T extends BaseModel> extends Result {


    private T mData;

    public void setData(T data){
         mData = data;
    }

    public T getData(){
        return mData;
    }
}
