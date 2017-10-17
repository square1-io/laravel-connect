package io.square1.connect.client.retrofit;

import android.content.Context;

import java.io.IOException;
import java.util.Map;
import java.util.concurrent.TimeUnit;


import io.square1.connect.client.Auth;
import io.square1.connect.client.LaravelConnectClient;
import io.square1.connect.client.LaravelConnectSettings;
import io.square1.connect.client.Request;
import io.square1.connect.client.Sort;
import io.square1.connect.client.gjson.GsonConverterFactory;
import io.square1.connect.model.BaseModel;
import io.square1.connect.model.ModelUtils;
import okhttp3.Interceptor;
import okhttp3.OkHttpClient;
import okhttp3.Response;
import okhttp3.logging.HttpLoggingInterceptor;
import retrofit2.Call;
import retrofit2.Retrofit;


/**
 * Created by roberto on 10/05/2017.
 */

public class RetrofitApiClient {


    private class AuthInterceptor implements Interceptor {

        @Override
        public Response intercept(Chain chain) throws IOException {

            okhttp3.Request.Builder ongoing = chain.request().newBuilder();
           // ongoing.addHeader("Accept", "application/json;versions=1");
            if (Auth.isLoggedIn()) {
                ongoing.addHeader("Authorization", "Bearer " + Auth.getToken());
            }
            return chain.proceed(ongoing.build());
        }
    }


    private Context mApplicationContext;

    private RetrofitService mRetrofitService;


    public RetrofitApiClient(Context context, LaravelConnectSettings settings){

        mApplicationContext = context.getApplicationContext();

        HttpLoggingInterceptor logging = new HttpLoggingInterceptor();
        logging.setLevel(HttpLoggingInterceptor.Level.HEADERS);

        OkHttpClient.Builder httpClient = new OkHttpClient.Builder();
        httpClient.addInterceptor(logging);

        String apiPath = settings.buildApiPath();

        Retrofit retrofit = new Retrofit.Builder().baseUrl(apiPath)
                .client(httpClient.connectTimeout(60, TimeUnit.SECONDS)
                        .readTimeout(60, TimeUnit.SECONDS)
                        .addInterceptor(new AuthInterceptor())
                        .followRedirects(true)
                        .build())
                .addConverterFactory(GsonConverterFactory.create())
                .build();

        mRetrofitService = retrofit.create(RetrofitService.class);
    }

//    protected  <T extends BaseModel> Request processRequest(Request<T> request) {
//
//        final Class<T> parentModel =  request.getModel();
//        final int objectId = request.getObjectId();
//        final Request.Method method = request.getMethod();
//        final String modelPath = ModelUtils.pathForModel(parentModel);
//        final String relationshipName = request.getRelationshipName();
//
//        Call call = null;
//        Class<T> responseModel =  null;
//        if(method == Request.Method.GET){
//
//            if(objectId > 0 ){
//                if(TextUtils.isEmpty(relationshipName) == true) {
//                    responseModel = parentModel;
//                    call = mRetrofitService.get(modelPath, objectId);
//                }else {
//                    responseModel = request.getRelationshipModel();
//                    call = mRetrofitService.get(modelPath, objectId, relationshipName);
//                }
//            }else {
//                responseModel = parentModel;
//                call = mRetrofitService.get(modelPath, null, null);
//            }
//
//        }
//
//        if(call != null){
//            call.enqueue(new RetrofitCallHandler(responseModel,request));
//        }
//
//        return request;
//    }


    public Request list(Class<? extends BaseModel> tModel,
                        int page,
                        int perPage,
                        LaravelConnectClient.Observer observer,
                        Sort... orderBy){

        String modelPath = ModelUtils.pathForModel(tModel);
        Call call = mRetrofitService.get(modelPath, page, perPage, null);
        RetrofitRequest retrofitRequest = new RetrofitRequest(call, new RetrofitCallHandler(tModel, observer));
        return retrofitRequest;

    }

    public Request list(Class<? extends BaseModel> tParentModel,
                        int parentId,
                        Class<? extends BaseModel> tRelationModel,
                        String relationName,
                        int page,
                        int perPage,
                        LaravelConnectClient.Observer observer,
                        Sort... orderBy){

        String modelPath = ModelUtils.pathForModel(tParentModel);
        Call call = mRetrofitService.get( modelPath, parentId, relationName, page, perPage, null);
        RetrofitRequest retrofitRequest = new RetrofitRequest(call, new RetrofitCallHandler(tRelationModel, observer));
        return retrofitRequest;

    }

    public Request show(Class<? extends BaseModel> tModel,
                        int id,
                        LaravelConnectClient.Observer observer){

        String modelPath = ModelUtils.pathForModel(tModel);
        Call call = mRetrofitService.get( modelPath, id);
        RetrofitRequest retrofitRequest = new RetrofitRequest(call, new RetrofitCallHandler(tModel, observer));
        return retrofitRequest;
    }

    public Request edit(BaseModel model, LaravelConnectClient.Observer observer){

        String modelPath = ModelUtils.pathForModel(model.getClass());
        Map<String, Object> maps = ModelUtils.updateParamsForModel(model);
        Call call = mRetrofitService.edit(modelPath, model.getId().getValue(), maps);
        RetrofitRequest retrofitRequest = new RetrofitRequest(call, new RetrofitCallHandler(model.getClass(), observer));
        return retrofitRequest;
    }

    public Request show(Class<? extends BaseModel> tParentModel,
                        int parentId,
                        Class<? extends BaseModel> tRelationModel,
                        String relationName,
                        int relationId,
                        LaravelConnectClient.Observer observer){

        String modelPath = ModelUtils.pathForModel(tParentModel);
        Call call = mRetrofitService.get( modelPath, parentId, relationName, relationId);
        RetrofitRequest retrofitRequest = new RetrofitRequest(call, new RetrofitCallHandler(tRelationModel, observer));
        return retrofitRequest;

    }

    public Request login(String email, String password, LaravelConnectClient.Observer observer){
        Call call = mRetrofitService.login(email, password);
        RetrofitAuthRequest retrofitRequest = new RetrofitAuthRequest(call, observer);
        return retrofitRequest;
    }

}
