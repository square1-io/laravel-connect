package io.square1.connect.model;

import java.lang.reflect.Field;
import java.util.ArrayList;
import java.util.Collection;
import java.util.Collections;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

/**
 * Created by roberto on 05/06/2017.
 */

public class ModelUtils {


    public static <T extends BaseModel> void copyValues(T destination, T origin){

        Class<T> clazz = (Class<T>) origin.getClass();

        List<Field> fields = getAllModelFields(clazz);

        if (fields != null) {
            for (Field field : fields) {
                try {
                    field.setAccessible(true);
                    field.set(destination, field.get(origin));
                } catch (IllegalAccessException e) {
                    e.printStackTrace();
                }
            }
        }


    }

    public static List<Field> getAllModelFields(Class aClass) {

        List<Field> fields = new ArrayList<>();
        do {
            Collections.addAll(fields, aClass.getDeclaredFields());
            aClass = aClass.getSuperclass();
        } while (aClass != null);
        return fields;
    }

    public static <T extends BaseModel> String pathForModel(Class<T> model){
        try {
            String path = (String) model.getMethod("getModelPath").invoke(null);
            return path;
        }catch (Exception e){
            return "";
        }
    }

    public static  Map<String, Object> updateParamsForModel(BaseModel model){

        HashMap<String, Object> updateFiels = new HashMap<>();

        Collection<ModelProperty> properties = model.mProperties.values();
        for ( ModelProperty property : properties ){

            if(property.needSave() == true){
                updateFiels.put(property.getName(), property.getValue());
            }
        }

        return updateFiels;

    }

}
