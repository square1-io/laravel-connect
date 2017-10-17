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

    @nonobjc public class func fetchRequest() -> NSFetchRequest<{{$className}}> {
        return NSFetchRequest<{{$className}}>(entityName: "{{$className}}")
    }

}
