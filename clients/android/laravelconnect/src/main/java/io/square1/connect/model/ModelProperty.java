package io.square1.connect.model;

/**
 * Created by roberto on 25/06/2017.
 */

public class ModelProperty<T> extends ModelAttribute  {

    private T mValue;
    private T mOldValue;

    private Class<T> mType;
    private String mName;

    public ModelProperty(BaseModel parent, String name, Class<T> type){
        super(parent, BaseModel.ATTRIBUTE_PROPERTY);
        mType = type;
        mName = name;
    }

    public Class<T> getPropertyClass(){
        return mType;
    }

    public final String getName(){
        return mName;
    }

    public void setValue(T value){
        mOldValue = mValue;
        mValue = value;
        if(mOldValue != null) {
            setNeedSave(true);
        }
    }

    public T getValue(){
        return mValue;
    }
}
