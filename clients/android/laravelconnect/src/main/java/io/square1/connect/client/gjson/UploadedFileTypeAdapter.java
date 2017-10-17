
package io.square1.connect.client.gjson;

import com.google.gson.JsonDeserializationContext;
import com.google.gson.JsonDeserializer;
import com.google.gson.JsonElement;
import com.google.gson.JsonParseException;

import java.lang.reflect.Type;

import io.square1.connect.model.UploadedFile;

public class UploadedFileTypeAdapter implements JsonDeserializer<UploadedFile> {

        public UploadedFile deserialize(JsonElement json,
                                   Type typeOfT,
                                   JsonDeserializationContext context) throws JsonParseException
        {
            String url = json.getAsString();
            return new UploadedFile(url);

        }
    }