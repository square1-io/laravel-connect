package io.square1.connect.client;

import io.square1.connect.client.results.Result;
import io.square1.connect.client.results.ResultList;
import io.square1.connect.model.BaseModel;

/**
 * Created by roberto on 10/05/2017.
 */

public class RequestOLD<T extends BaseModel> {


    public String getRelationshipName() {
        return mRelation;
    }

    public enum Method {
        GET,
        POST
    }

    public interface Observer<T extends Result> {
        void onRequestCompleted(T result);
    }

    private int mPage;
    private int mLimith;
    private int mId;
    private Method mMethod;

    private Class<T> mModel;
    private Class<T> mRelationModel;
    private Observer mObserver;
    private String mRelation;
    private int mParentId;
    private LaravelConnectClient mClient;

    public RequestOLD(Class<T> tModel){
        mClient = LaravelConnectClient.getInstance();
        mModel = tModel;
        mMethod = Method.GET;
        mId = -1;
    }



    public Class<T> getModel(){
        return mModel;
    }


    public Class<T> getRelationshipModel(){
        return mRelationModel;
    }



    public int getObjectId(){
        return mId;
    }

    public Method getMethod() {
        return mMethod;
    }

    public Observer getObserver() {
        return mObserver;
    }

    //index
    public RequestOLD get(int page, int limit, Observer observer){
        mMethod = Method.GET;
        mPage = page;
        mLimith = limit;
        mObserver = observer;
       return this;
    }

    //index a relation
    public RequestOLD get(int id, Class<T> relationClass, String relationName, int page, int limit, Observer observer){
        mId = id;
        mRelationModel = relationClass;
        mRelation = relationName;
        mMethod = Method.GET;
        mObserver = observer;
        return this;
    }

    //show
    public RequestOLD get(int id, Observer observer){
        mId = id;
        mMethod = Method.GET;
        mObserver = observer;
        return this;
    }

    //edit or store depending on id != 0
    public RequestOLD save(T object, Observer observer){
        mMethod = Method.POST;
        mObserver = observer;
        return this;
    }

    public RequestOLD start(){
       // mClient.processRequest(this);
        return this;
    }


    public static RequestOLD index(Class<? extends BaseModel> modelClass,
                                   int page,
                                   int limit,
                                   RequestOLD.Observer<ResultList> observer){

        return new RequestOLD(modelClass).get(page, limit, observer).start();
    }

}
