package io.square1.connect.model;

import java.util.HashMap;

import io.square1.connect.client.LaravelConnectClient;
import io.square1.connect.client.Request;
import io.square1.connect.client.Sort;
import io.square1.connect.client.results.Result;

/**
 * Created by roberto on 10/05/2017.
 */

public abstract class BaseModel  {

    public static final int ATTRIBUTE_PROPERTY = 1;
    public static final int ATTRIBUTE_REL_MANY = 2;
    public static final int ATTRIBUTE_REL_ONE = 3;



    /*package*/ HashMap<String, ModelManyRelation> mManyRelations;
    /*package*/ HashMap<String, ModelOneRelation> mRelations;
    /*package*/ HashMap<String, ModelProperty> mProperties;
    /*package*/ HashMap<String, ModelAttribute> mAttributes;


    private  class InternalObserver implements LaravelConnectClient.Observer {

        private LaravelConnectClient.Observer mWrappedObserver;

        public InternalObserver(LaravelConnectClient.Observer observer){
            mWrappedObserver = observer;
        }

        @Override
        public void onRequestCompleted(Result result) {

            if(result.isSuccessful()){
                BaseModel model = (BaseModel)result.getData();
                ModelUtils.copyValues(BaseModel.this, model);
            }
            if(mWrappedObserver != null){
                mWrappedObserver.onRequestCompleted(result);
            }
        }

    }

    public final static int ID_UNSET = -1;


    private  ModelProperty<Integer> mId;

    public ModelProperty<Integer> getId(){
        return mId;
    }

    void setId(int id) {
        mId.setValue(id);
    }


    public BaseModel(){
        mId = new ModelProperty<>(this, "id", Integer.class);
        mId.setValue(ID_UNSET);

        mRelations = new HashMap<>();
        mManyRelations = new HashMap<>();
        mProperties = new HashMap<>();
        mAttributes = new HashMap<>();
    }


    protected final ModelProperty addProperty(String name, Class<?> tClass) {
        ModelProperty newProperty = new ModelProperty(this, name, tClass);
        mProperties.put(name, newProperty);
        mAttributes.put(name, newProperty);
        return newProperty;
    }

    protected final ModelManyRelation addRelation(String name, Class<? extends BaseModel> tClass) {
        ModelManyRelation newRelation = new ModelManyRelation(this,name, tClass);
        mManyRelations.put(name,newRelation);
        mAttributes.put(name, newRelation);
        return newRelation;
    }

    protected final ModelOneRelation addRelation(String name, String key, Class<? extends BaseModel> tClass) {

        try {
            BaseModel model = tClass.newInstance();
            ModelOneRelation relation = new ModelOneRelation(this, name, key, model);
            mRelations.put(key, relation);
            mAttributes.put(name, relation);
            return  relation;
        }catch (Exception e){

        }
        return null;
    }

    public final HashMap<String, ModelAttribute> getAttributes(){
        return mAttributes;
    }



    public boolean isSet(){
        return mId.getValue() != ID_UNSET;
    }

    public void refresh(LaravelConnectClient.Observer observer){

        if(isSet() == true){
            LaravelConnectClient apiClient = LaravelConnectClient.getInstance();
            apiClient.show(getClass(), mId.getValue(), new InternalObserver(observer));
        }
    }

    public void save(LaravelConnectClient.Observer observer){

        LaravelConnectClient apiClient = LaravelConnectClient.getInstance();
        if(isSet() == true){
            apiClient.update(this, new InternalObserver(observer));
        }else {
            apiClient.create(this, new InternalObserver(observer));
        }

    }


    /**
     *
     * @param modelClass
     * @param page
     * @param perPage
     * @param observer
     * @return
     */
    protected static Request index(Class<? extends BaseModel> modelClass,
                               int page,
                               int perPage,
                               LaravelConnectClient.Observer observer,
                                   Sort... orderBy){

        LaravelConnectClient apiClient = LaravelConnectClient.getInstance();
        return apiClient.list(modelClass, page, perPage, observer, orderBy);

    }



    protected static Request show(Class<? extends BaseModel> modelClass,
                               int id, LaravelConnectClient.Observer observer){

        LaravelConnectClient apiClient = LaravelConnectClient.getInstance();
        return apiClient.show(modelClass, id, observer);

    }



}
