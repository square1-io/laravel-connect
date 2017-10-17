package io.square1.connect.client;

import io.square1.connect.model.ModelAttribute;

/**
 * Created by roberto on 27/06/2017.
 */

public class Sort {

    public static final int ASC = 1;
    public static final int DESC = 2;

    private ModelAttribute mAttribute;
    private int mOrder;

    public Sort(ModelAttribute attribute){
        mAttribute = attribute;
        mOrder = ASC;
    }

}
