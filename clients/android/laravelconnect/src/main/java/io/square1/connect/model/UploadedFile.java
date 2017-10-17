package io.square1.connect.model;


/**
 * Created by roberto on 16/04/2017.
 */

public class UploadedFile  {

    private String mUrl;

    public UploadedFile(String url){
        mUrl = url;
    }

    public String getUrl(){
        return mUrl;
    }

}
