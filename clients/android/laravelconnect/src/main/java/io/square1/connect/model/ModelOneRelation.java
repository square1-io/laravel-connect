package io.square1.connect.model;

/**
 * Created by roberto on 05/06/2017.
 */

public class ModelOneRelation<T extends BaseModel> extends ModelAttribute{

    private String mRelationName;
    private String mPrimaryKey;
    private T mRelation;

    public ModelOneRelation(BaseModel parent, String name, String primaryKey, T model){
        super(parent, BaseModel.ATTRIBUTE_REL_ONE);

        mRelationName = name;
        mPrimaryKey = primaryKey;
        mRelation = model;
    }

    public final T getValue(){
        return mRelation;
    }

    public void setValue(T value){
        mRelation = value;
    }

}
