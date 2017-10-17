package io.square1.connect.model;

/**
 * Created by roberto on 26/06/2017.
 */

public class ModelFactory {


//    public static final void resolveRelations(Gson gson, JsonObject jsonObject, BaseModel currentObject, boolean clear){
//
//        for(ModelManyRelation relation : currentObject.mManyRelations){
//            ///see if relation present in Json
//            JsonElement relationJson = jsonObject.get(relation.getName());
//            if(relationJson != null && relationJson instanceof JsonArray){
//                for(JsonElement object : (JsonArray)relationJson) {
//                    BaseModel baseModel = (BaseModel) gson.fromJson(object, relation.getRelationClass() );
//                    relation.add(baseModel);
//                }
//            }
//        }
//
//        for (Map.Entry<String, ModelOneRelation> entrySet : currentObject.mRelations.entrySet()){
//            String key = entrySet.getKey();
//            JsonElement relationJson = jsonObject.get(key);
//            if(relationJson != null){
//                int id = relationJson.getAsInt();
//                if(entrySet.getValue().getModel().isSet() == false){
//                    entrySet.getValue().getModel()._id = id;
//                }
//            }
//        }
//    }
}
