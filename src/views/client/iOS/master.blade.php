@include('ios::partials.header')

@include('ios::partials.imports')

@objc({{$className}})
public class {{$className}}: NSManagedObject, Decodable {

    enum CodingKeys: String, CodingKey  {
        @each('ios::partials._codingkey', $members, 'property')
    }

    class var modelPath: String {       
        return "{{urlencode($classPath)}}"
    }
    
    class var primaryKey: String {       
        return "{{$primaryKey}}"
    }

    required convenience public init(from decoder: Decoder) throws {
        // Create NSEntityDescription with NSManagedObjectContext
        guard let contextUserInfoKey = CodingUserInfoKey.context,
            let managedObjectContext = decoder.userInfo[contextUserInfoKey] as? NSManagedObjectContext,
            let entity = NSEntityDescription.entity(forEntityName: "{{$className}}", in: managedObjectContext) else {
                fatalError("Failed to decode {{$className}}!")
        }
        self.init(entity: entity, insertInto: nil)
        
        // Decode
        let values = try decoder.container(keyedBy: CodingKeys.self)
        @each('ios::partials._decoding', $members, 'property')
    }

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
