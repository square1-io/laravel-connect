@include('android::partials.header')

@include('android::partials.package')

@include('android::partials.imports')

public class {{$className}} extends BaseModel {

    public final static String PRIMARY_KEY = "{{$primaryKey}}";
    
    @each('android::partials._member', $members, 'member')
    
    @each('android::partials._relation', $relations, 'member')

    public {{$className}}() {
      super(PRIMARY_KEY);
      @each('android::partials._member_init', $members, 'member') 
      
      @each('android::partials._relation_init', $relations, 'relation') 
      }

    @each('android::partials._getter_setter', $members, 'member')    

    @each('android::partials._relation_getter', $relations, 'relation') 
    
   public static ModelList list(){
        return ModelList.listForModel({{$className}}.class);
    }

    public static ApiRequest get(int id, LaravelConnectClient.Observer observer){

        return BaseModel.get( {{$className}}.class, id, observer);
    }
    
    @foreach($endpoints as $endpoint)
    @include('android::partials._request', ['endpoint'=>$endpoint, 'className'=>$className])
    @endforeach
   
    
    public static final String getModelPath(){
      return "{{urlencode($classPath)}}";
    }
}