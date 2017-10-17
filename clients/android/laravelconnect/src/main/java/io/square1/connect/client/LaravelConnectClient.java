package io.square1.connect.client;

import android.content.Context;

import io.square1.connect.client.results.Result;
import io.square1.connect.client.retrofit.RetrofitApiClient;
import io.square1.connect.model.BaseModel;

/**
 * Created by roberto on 27/06/2017.
 */

public class LaravelConnectClient {

    public interface Observer {
        void onRequestCompleted(Result result);
    }

    private static LaravelConnectClient sInstance;


    private LaravelConnectSettings mSettings;
    private RetrofitApiClient mClientImplementation;
    private Context mApplicationContext;

    public synchronized static LaravelConnectClient init(Context context, LaravelConnectSettings settings) {

        if(sInstance == null){
            sInstance = new LaravelConnectClient(context, settings);
        }

        return sInstance;
    }

    public synchronized static LaravelConnectClient getInstance() {

        if(sInstance == null){
            throw  new RuntimeException("api client not initialised , call init at application start");
        }

        return sInstance;
    }


    private LaravelConnectClient(Context context, LaravelConnectSettings settings){
        Auth.init(context);

        mSettings = settings;
        mApplicationContext = context.getApplicationContext();
        mClientImplementation = new RetrofitApiClient(context, settings);
    }


    public Request list(Class<? extends BaseModel> tModel,
                     int page,
                     int perPage,
                     Observer observer,
                     Sort... orderBy){

        Request request =  mClientImplementation.list(tModel, page, perPage, observer, orderBy);
        request.execute();
        return request;
    }

    public Request list(Class<? extends BaseModel> tModel,
                        int parentId,
                        Class<? extends BaseModel> tRelationClass,
                        String relationName,
                        int page,
                        int perPage,
                        Observer observer,
                        Sort... orderBy){


        Request request =  mClientImplementation.list(tModel, parentId, tRelationClass,
                relationName, page, perPage, observer, orderBy);

        request.execute();

        return request;
    }


    public Request show(BaseModel model,  Observer observer){
        return show(model.getClass(), model.getId().getValue(), observer);

    }

    public Request show(Class<? extends BaseModel> tModel, int id, Observer observer){

        Request request =  mClientImplementation.show(tModel, id, observer);
        request.execute();
        return request;

    }

    public Request update(BaseModel model, Observer observer){

        Request request =  mClientImplementation.edit(model, observer);
        request.execute();
        return request;
    }

    public void create(BaseModel model, Observer observer){

    }

    public Request login(String email, String password, LaravelConnectClient.Observer observer){
        Request request =  mClientImplementation.login(email, password, observer);
        request.execute();
        return request;
    }


}
