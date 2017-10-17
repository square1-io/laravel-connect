package io.square1.connect.client;

import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.SharedPreferences;
import android.preference.PreferenceManager;
import android.text.TextUtils;

import java.util.ArrayList;

import io.square1.connect.model.BaseModel;

/**
 * Created by roberto on 11/07/2017.
 */

public class Auth {

    public interface AuthStateChangedObserver {
        void onLoginStateChanged();
    }

    private static Auth sInstance;


    synchronized static Auth init(Context context){

        if(sInstance == null){
            sInstance = new Auth(context);
        }
        return sInstance;
    }

    private static final String PREF_JWT_TOKEN =  "PREF_JWT_TOKEN";

    private BaseModel mCurrentUser;
    private Context mApplicationContext;
    private ArrayList<AuthStateChangedObserver> mObservers;

    private Auth(Context context){
        mApplicationContext = context.getApplicationContext();
        mObservers = new ArrayList<>();
    }

    private String loadToken(){

        SharedPreferences sharedPreferences = PreferenceManager
                .getDefaultSharedPreferences(mApplicationContext);
        return sharedPreferences.getString(PREF_JWT_TOKEN, "");

    }

    private void storeToken(String token){

        SharedPreferences sharedPreferences = PreferenceManager
                .getDefaultSharedPreferences(mApplicationContext);
        SharedPreferences.Editor editor = sharedPreferences.edit();
        editor.putString(PREF_JWT_TOKEN, token);
        editor.commit();

        ArrayList<AuthStateChangedObserver> observers = new ArrayList<>(mObservers);
        for(AuthStateChangedObserver observer : observers){
            observer.onLoginStateChanged();
        }
    }

    private BaseModel loadCurrentUser(){
        return mCurrentUser;
    }

    private void storeCurrentUser(BaseModel user){
        mCurrentUser = user;
    }

    public static boolean isLoggedIn(){
        String token =  sInstance.getToken();
        return TextUtils.isEmpty(token) == false;
    }

    public static void setToken(String token){
        sInstance.storeToken(token);
    }

    public static void clearToken(){
        sInstance.storeToken("");
    }

    public static String getToken(){
        return sInstance.loadToken();
    }

    public static void setCurrentUser(BaseModel baseModel){
        sInstance.storeCurrentUser(baseModel);
    }

    public static BaseModel getCurrentUser(){
        return sInstance.loadCurrentUser();
    }

    public static void registerNotifications(AuthStateChangedObserver observer){
        if(sInstance.mObservers.contains(observer) == false){
            sInstance.mObservers.add(observer);
        }
    }
    public static void unregisterObserver(AuthStateChangedObserver observer){
        sInstance.mObservers.remove(observer);
    }

    public static Request login(String email, String password, LaravelConnectClient.Observer observer){
        LaravelConnectClient connectClient = LaravelConnectClient.getInstance();
        return connectClient.login(email, password, observer);
    }

}
