package io.square1.connect.client.retrofit;

import com.google.gson.JsonObject;

import java.util.HashMap;
import java.util.Map;

import retrofit2.Call;
import retrofit2.http.Body;
import retrofit2.http.Field;
import retrofit2.http.FormUrlEncoded;
import retrofit2.http.GET;
import retrofit2.http.POST;
import retrofit2.http.Path;
import retrofit2.http.Query;

/**
 * Created by roberto on 26/05/2017.
 */

public interface RetrofitService {

    @GET("{model}")
     Call<JsonObject> get(@Path("model") String model,
                          @Query("page") Integer page,
                          @Query("per_page") Integer per_page,
                          @Query("sort_by") HashMap<String,String> sortBy);

    @GET("{model}/{id}")
    Call<JsonObject> get(@Path("model") String model,
                         @Path("id") Integer id);

    @POST("{model}/{id}")
    Call<JsonObject> edit(@Path("model") String model,
                          @Path("id") Integer id,
                          @Body Map<String, Object> parameters);

    @GET("{model}/{id}/{relation}")
    Call<JsonObject> get(@Path("model") String model,
                         @Path("id") Integer id,
                         @Path("relation") String relation,
                         @Query("page") Integer page,
                         @Query("per_page") Integer per_page,
                         @Query("sort_by") HashMap<String,String> sortBy);

    @GET("{model}/{id}/{relation}/{relationId}")
    Call<JsonObject> get(@Path("model") String model,
                         @Path("id") Integer id,
                         @Path("relation") String relation,
                         @Path("id") Integer relationId);


    @POST("auth/login")
    @FormUrlEncoded
    Call<JsonObject> login(@Field("email") String email,
                           @Field("password") String password);
}
