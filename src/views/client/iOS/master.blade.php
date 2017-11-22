@include('ios::partials.header')

@include('ios::partials.imports')

@objc({{$className}})
public class {{$className}}: NSManagedObject {

    class var modelPath: String {       
        return "{{urlencode($classPath)}}"
    }
    
    class var primaryKey: String {       
        return "{{$primaryKey}}"
    }

}

extension {{$className}} {

@each('ios::partials._property', $members, 'property') 

@each('ios::partials._relation', $relations, 'relation') 

@each('ios::partials._relation_setters', $relations, 'relation') 

    @nonobjc public class func fetchRequest() -> NSFetchRequest<{{$className}}> {
        return NSFetchRequest<{{$className}}>(entityName: "{{$className}}")
    }

}
