package io.square1.connect.client.retrofit;

import com.google.gson.Gson;
import com.google.gson.JsonObject;

import io.square1.connect.client.LaravelConnectClient;
import io.square1.connect.client.gjson.GsonConverterFactory;
import io.square1.connect.client.results.Result;
import io.square1.connect.client.results.ResultFactory;
import io.square1.connect.model.BaseModel;
import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

/**
 * Created by roberto on 02/06/2017.
 */

public class RetrofitCallHandler<T extends BaseModel> implements Callback<JsonObject> {


    private Class<T> mModel;
    private LaravelConnectClient.Observer mObserver;

    public RetrofitCallHandler(Class<T> model, LaravelConnectClient.Observer observer){
        mModel = model;
        mObserver = observer;
    }

    @Override
    public void onResponse(Call<JsonObject> call, Response<JsonObject> response) {

        if(mObserver != null){
            Gson sGson = GsonConverterFactory.create().getGson();
            Result result = ResultFactory.getInstance(sGson,response.body(),mModel);
            mObserver.onRequestCompleted(result);
        }
    }

    @Override
    public void onFailure(Call<JsonObject> call, Throwable t) {

        if(mObserver != null){
            Result result = ResultFactory.getInstance(t);
            mObserver.onRequestCompleted(result);
        }
    }




}
