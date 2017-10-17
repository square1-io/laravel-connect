package io.square1.connect.client.gjson;


import com.google.gson.ExclusionStrategy;
import com.google.gson.FieldAttributes;
import com.google.gson.Gson;
import com.google.gson.GsonBuilder;
import com.google.gson.TypeAdapter;
import com.google.gson.reflect.TypeToken;
import java.lang.annotation.Annotation;
import java.lang.reflect.Type;


import io.square1.connect.model.ModelManyRelation;
import io.square1.connect.model.UploadedFile;
import okhttp3.RequestBody;
import okhttp3.ResponseBody;
import retrofit2.Converter;
import retrofit2.Retrofit;

public final class GsonConverterFactory extends Converter.Factory {

  private static GsonConverterFactory sInstance;

  public static synchronized GsonConverterFactory create() {
    if(sInstance == null){
      sInstance = create(newGson());
    }
    return sInstance;
  }


  public static synchronized GsonConverterFactory create(Gson gson) {
    if (gson == null) throw new NullPointerException("gson == null");
    return new GsonConverterFactory(gson);
  }


  private static Gson newGson(){

    GsonBuilder builder = new GsonBuilder();
    builder.registerTypeAdapter(Boolean.class, new BooleanTypeAdapter())
            .registerTypeAdapter(UploadedFile.class, new UploadedFileTypeAdapter())
            .serializeNulls()
            .setExclusionStrategies(new InternalExclusionStrategy())
            .setDateFormat("yyyy-mm-dd HH:mm:ss");//2017-05-29 20:28:13

    Gson gson = builder.create();
    return gson;
  }

  private final Gson gson;

  public Gson getGson(){
    return gson;
  }

  private GsonConverterFactory(Gson gson) {
    this.gson = gson;
  }

  @Override
  public Converter<ResponseBody, ?> responseBodyConverter(Type type, Annotation[] annotations,
                                                          Retrofit retrofit) {
    TypeAdapter<?> adapter = gson.getAdapter(TypeToken.get(type));

    return new GsonResponseBodyConverter<>(gson, adapter);
  }

  @Override
  public Converter<?, RequestBody> requestBodyConverter(Type type, Annotation[] parameterAnnotations, Annotation[] methodAnnotations, Retrofit retrofit) {
    TypeAdapter<?> adapter = gson.getAdapter(TypeToken.get(type));
    return new GsonRequestBodyConverter<>(gson, adapter);
  }


  private static class InternalExclusionStrategy implements ExclusionStrategy {

    @Override
    public boolean shouldSkipField(FieldAttributes f) {
      return false;
    }

    @Override
    public boolean shouldSkipClass(Class<?> clazz) {
      return ModelManyRelation.class == clazz;
    }
  }

}