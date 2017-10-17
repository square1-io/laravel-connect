
package io.square1.connect.client.gjson;

import com.google.gson.JsonDeserializationContext;
import com.google.gson.JsonDeserializer;
import com.google.gson.JsonElement;
import com.google.gson.JsonParseException;

import java.lang.reflect.Type;

public class BooleanTypeAdapter implements JsonDeserializer<Boolean> {

        public Boolean deserialize(JsonElement json,
                                   Type typeOfT,
                                   JsonDeserializationContext context) throws JsonParseException
        {
            String code = json.getAsString();
            if("true".equalsIgnoreCase(code)){
                return true;
            }
            if("false".equalsIgnoreCase(code)){
                return false;
            }

            int intValue = Integer.parseInt(code);

            return intValue == 0 ? false :
                    intValue == 1 ? true :
                            null;
        }
    }