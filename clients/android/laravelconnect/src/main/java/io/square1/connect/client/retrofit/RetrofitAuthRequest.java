package io.square1.connect.client.retrofit;

import com.google.gson.JsonObject;

import io.square1.connect.client.Auth;
import io.square1.connect.client.LaravelConnectClient;
import io.square1.connect.client.Request;
import io.square1.connect.client.results.ResultFactory;
import io.square1.connect.client.results.ResultObj;
import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

/**
 * Created by roberto on 11/07/2017.
 */

public class RetrofitAuthRequest implements Request, Callback<JsonObject> {


    private Call<JsonObject> mCall;
    private LaravelConnectClient.Observer mObserver;

    public RetrofitAuthRequest(Call call, LaravelConnectClient.Observer observer){
        mCall = call;
        mObserver = observer;
    }

    @Override
    public void cancel() {

    }

    @Override
    public void execute() {
        mCall.enqueue(this);
    }

    @Override
    public void onResponse(Call<JsonObject> call, Response<JsonObject> response) {

        JsonObject data = response.body().getAsJsonObject("data");
        String token = data.get("token").getAsString();

        Auth.setToken(token);

        if(mObserver != null){
            mObserver.onRequestCompleted(new ResultObj<>());
        }
    }

    @Override
    public void onFailure(Call call, Throwable t) {
        Auth.clearToken();

        if(mObserver != null){
            mObserver.onRequestCompleted(ResultFactory.getInstance(t));
        }
    }
}
