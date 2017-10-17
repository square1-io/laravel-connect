package io.square1.connect.client.results;

import com.google.gson.Gson;
import com.google.gson.JsonArray;
import com.google.gson.JsonElement;
import com.google.gson.JsonObject;

import java.util.ArrayList;

import io.square1.connect.client.gjson.GSonModelFactory;
import io.square1.connect.model.BaseModel;

/**
 * Created by roberto on 03/06/2017.
 */

public class ResultFactory {


  public static <T extends BaseModel> Result getInstance(Gson gson,
                                                         JsonObject resultJson,
                                                         Class<T> tClass){

      //step 1 check if data is available
      JsonObject data = resultJson.get("data").getAsJsonObject();
      if(data != null){
          JsonElement elements = data.get("data");
          if(elements != null && elements instanceof JsonArray){
              return getInstance(gson, data, (JsonArray)elements, tClass);
          }else {
              return getInstance(gson, resultJson, data, tClass);
          }
      }

      return null;
  }

    public static <T extends BaseModel> Result getInstance(Throwable throwable){
        ResultObj resultObj = new ResultObj();
        resultObj.setError(new ResultError(throwable));
        return resultObj;
    }


  private static <T extends BaseModel> ResultList getInstance(Gson gson,
                                                              JsonObject resultJson,
                                                              JsonArray data,
                                                              Class<T> tClass){

      Pagination pagination = gson.fromJson(resultJson, Pagination.class);
      ResultList result = gson.fromJson(resultJson, ResultList.class);
      result.setPagination(pagination);
      ArrayList arrayList = GSonModelFactory.getModelListInstance(gson, data, tClass);
      result.getData().addAll(arrayList);
      return result;
  }

    private static <T extends BaseModel> ResultObj getInstance(Gson gson,
                                                               JsonObject resultJson,
                                                               JsonObject data,
                                                               Class<T> tClass){

        ResultObj<T> result = gson.fromJson(resultJson, ResultObj.class);
        T object = GSonModelFactory.getModelInstance(gson, data, tClass);
        result.setData(object);
        return result;
    }
}
