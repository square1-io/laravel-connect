package io.square1.connect.model;

import android.support.annotation.NonNull;



import java.util.ArrayList;
import java.util.Collection;
import java.util.Iterator;
import java.util.List;
import java.util.ListIterator;

import io.square1.connect.client.LaravelConnectClient;
import io.square1.connect.client.Request;
import io.square1.connect.client.results.Pagination;
import io.square1.connect.client.results.Result;

/**
 * Created by roberto on 03/06/2017.
 */

public class ModelManyRelation<T extends BaseModel> extends ModelAttribute implements List<T>, LaravelConnectClient.Observer {

    private String mRelationName;
    private Class<T> mRelationClass;
    private LaravelConnectClient.Observer mObserver;
    private Pagination mCurrentPage;
    private ArrayList<T> mStorage;

    public ModelManyRelation(BaseModel parent, String name, Class modelType){
        super(parent,BaseModel.ATTRIBUTE_REL_MANY);
        mCurrentPage = Pagination.NOPAGE;
        mRelationName = name;
        mRelationClass = modelType;
        mStorage = new ArrayList<>();
    }


    public String getName(){
        return mRelationName;
    }

    public Class<T> getRelationClass(){
        return mRelationClass;
    }

    public void next(LaravelConnectClient.Observer observer){
        if(mCurrentPage.hasNext() == true) {
            index(mCurrentPage.next(), mCurrentPage.getPageSize(), observer);
        }
    }

    private   Request index( int page, int perPage, LaravelConnectClient.Observer observer){

        mObserver = observer;
        LaravelConnectClient apiClient = LaravelConnectClient.getInstance();
        return apiClient.list(getParent().getClass(),
                getParent().getId().getValue(),
                mRelationClass, mRelationName,
                page, perPage, observer);
     }



    @Override
    public int size() {
        return mStorage.size();
    }

    @Override
    public boolean isEmpty() {
        return mStorage.isEmpty();
    }

    @Override
    public boolean contains(Object o) {
        return mStorage.contains(o);
    }

    @NonNull
    @Override
    public Iterator<T> iterator() {
        return mStorage.iterator();
    }

    @NonNull
    @Override
    public Object[] toArray() {
        return mStorage.toArray();
    }

    @NonNull
    @Override
    public <T1> T1[] toArray(@NonNull T1[] t1s) {
        return mStorage.toArray(t1s);
    }

    @Override
    public boolean add(T t) {
        return mStorage.add(t);
    }

    @Override
    public boolean remove(Object o) {
        return mStorage.remove(o);
    }

    @Override
    public boolean containsAll(@NonNull Collection<?> collection) {
        return mStorage.containsAll(collection);
    }

    @Override
    public boolean addAll(@NonNull Collection<? extends T> collection) {
        return mStorage.addAll(collection);
    }

    @Override
    public boolean addAll(int i, @NonNull Collection<? extends T> collection) {
        return mStorage.addAll(i, collection);
    }

    @Override
    public boolean removeAll(@NonNull Collection<?> collection) {
        return mStorage.removeAll(collection);
    }

    @Override
    public boolean retainAll(@NonNull Collection<?> collection) {
        return mStorage.retainAll(collection);
    }

    @Override
    public void clear() {
        mStorage.clear();
    }

    @Override
    public T get(int i) {
        return mStorage.get(i);
    }

    @Override
    public T set(int i, T t) {
        return mStorage.set(i, t);
    }

    @Override
    public void add(int i, T t) {
        mStorage.add(i, t);
    }

    @Override
    public T remove(int i) {
        return mStorage.remove(i);
    }

    @Override
    public int indexOf(Object o) {
        return mStorage.indexOf(o);
    }

    @Override
    public int lastIndexOf(Object o) {
        return mStorage.lastIndexOf(o);
    }

    @Override
    public ListIterator<T> listIterator() {
        return mStorage.listIterator();
    }

    @NonNull
    @Override
    public ListIterator<T> listIterator(int i) {
        return mStorage.listIterator(i);
    }

    @NonNull
    @Override
    public List<T> subList(int i, int i1) {
        return mStorage.subList(i, i1);
    }

    @Override
    public void onRequestCompleted(Result result) {

//        if(result != null){
//            mCurrentPage = result.getPagination();
//            if(mCurrentPage.isFirstPage() == true){
//                clear();
//            }
//            addAll(result.getData());
//        }
//
//        if(mObserver != null){
//            mObserver.onRequestCompleted(result);
//        }

    }
}
