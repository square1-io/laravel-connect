package io.square1.connect.model;

/**
 * Created by roberto on 26/06/2017.
 */

public abstract class ModelAttribute {


    private boolean mNeedSave;
    private BaseModel mParent;
    private int mType;

    public ModelAttribute(){}

    public ModelAttribute(BaseModel parent, int type){
        mParent = parent;
        mType =  type;
    }


    public BaseModel getParent(){
        return mParent;
    }

    public final int getType(){
        return mType;
    }

    public boolean needSave(){
        return mNeedSave;
    }



    public void setNeedSave(boolean needSave){
        mNeedSave = needSave;
    }

}
