package io.square1.connect.client.results;

import com.google.gson.annotations.SerializedName;

/**
 * Created by roberto on 03/06/2017.
 */

public class Pagination {

    public static final int UNDEFINED = -234567;

    public static final Pagination NOPAGE = new Pagination();

    @SerializedName("current_page")
    private int mCurrentPage;

    @SerializedName("total")
    private int mTotalCount;

    @SerializedName("per_page")
    private int mPageSize;

    public int getCurrentPage() {
        return mCurrentPage;
    }

    public int getTotalCount() {
        return mTotalCount;
    }

    public int getPageSize() {
        return mPageSize;
    }

    public Pagination(){
        mCurrentPage = 0;
        mPageSize = 10;
        mTotalCount = UNDEFINED;
    }

    public final int pageCount(){
        int count = mTotalCount / mPageSize;
        int mod = mTotalCount % mPageSize;
        return count + ((mod > 0) ? 1 : 0);
    }

    public boolean hasNext(){
        return mTotalCount < 0 ||  mCurrentPage < pageCount();
    }

    public int next(){
       return mCurrentPage + 1;
    }

    public boolean isFirstPage(){
        return mCurrentPage == 1;
    }

}
