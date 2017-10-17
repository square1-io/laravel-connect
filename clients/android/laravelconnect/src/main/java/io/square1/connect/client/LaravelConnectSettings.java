package io.square1.connect.client;

import android.net.Uri;
import android.webkit.URLUtil;

import java.net.URL;
import java.util.IllegalFormatException;

/**
 * Created by roberto on 09/07/2017.
 */

public class LaravelConnectSettings {

    public String getBasePath(){
        return "square1/connect/";
    }

    //.baseUrl("http://192.168.0.188:8000/apify/")
    //            .baseUrl("http://192.168.0.31:8000/apify/")
    //.baseUrl("http://www.apify.local/")
    //192.168.0.31

    public String getApiAddress(){
        return "http://192.168.0.31:8000";
    }



    public final String buildApiPath() throws RuntimeException{

        if(URLUtil.isNetworkUrl(getApiAddress())){
            Uri.Builder builder = Uri.parse(getApiAddress()).buildUpon();
            builder.appendEncodedPath(getBasePath());

            String path = builder.build().toString();

            if(URLUtil.isNetworkUrl(path) == true) {
                return path;
            }else {
                throw new RuntimeException("api address is not valid -> " + path);
            }

        }
        else {
            throw new RuntimeException("api address is not valid -> " + getApiAddress());
        }
    }


}
