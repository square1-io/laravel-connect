package io.square1.connect.client.gjson;

import com.google.gson.Gson;
import com.google.gson.JsonArray;
import com.google.gson.JsonElement;
import com.google.gson.JsonObject;

import java.util.ArrayList;
import java.util.Map;
import java.util.Set;

import io.square1.connect.model.BaseModel;
import io.square1.connect.model.ModelAttribute;
import io.square1.connect.model.ModelManyRelation;
import io.square1.connect.model.ModelOneRelation;
import io.square1.connect.model.ModelProperty;

/**
 * Created by roberto on 26/06/2017.
 */

public class GSonModelFactory {


    public static <T extends BaseModel> T getModelInstance(Gson gson, JsonObject jsonObject, Class<T> tClass) {

        try {

            T model = tClass.newInstance();

            //set the Id
            int id = jsonObject.get("id").getAsInt();
            model.getId().setValue(id);

            //loop over the attributes and set values
            final Set<Map.Entry<String, ModelAttribute>> attributesSet =  model.getAttributes()
                    .entrySet();

            for(Map.Entry<String, ModelAttribute> entry : attributesSet) {

                JsonElement jsonElement = jsonObject.get(entry.getKey());

                if(jsonElement != null){
                    //parsing basic properties
                    if(entry.getValue().getType() == BaseModel.ATTRIBUTE_PROPERTY){
                        ModelProperty modelProperty = (ModelProperty)entry.getValue();
                        modelProperty.setValue(gson.fromJson(jsonElement, modelProperty.getPropertyClass()));

                    }
                    else if(entry.getValue().getType() == BaseModel.ATTRIBUTE_REL_ONE){

                        ModelOneRelation modelOneRelation = (ModelOneRelation)entry.getValue();
                        BaseModel relationObject = getModelInstance(gson, (JsonObject)jsonElement,
                                modelOneRelation.getValue().getClass())  ;
                        modelOneRelation.setValue(relationObject);
                    }
                    else if(entry.getValue().getType() == BaseModel.ATTRIBUTE_REL_MANY){

                        ModelManyRelation modelManyRelation = (ModelManyRelation)entry.getValue();
                        modelManyRelation.clear();
                        ArrayList objects = getModelListInstance(gson,
                                (JsonArray)jsonElement ,
                                modelManyRelation.getRelationClass());

                        modelManyRelation.addAll(objects);
                    }

                }
            }

            return model;
        }catch (Exception e){

        }

        return null;
    }

    public static <T extends BaseModel> ArrayList<T> getModelListInstance(Gson gson, JsonArray jsonArray, Class<T> tClass){

        ArrayList arrayList = new ArrayList();

        for(JsonElement element : jsonArray){
            BaseModel model = getModelInstance(gson, (JsonObject)element, tClass);
            arrayList.add(model);
        }

        return arrayList;
    }
}
