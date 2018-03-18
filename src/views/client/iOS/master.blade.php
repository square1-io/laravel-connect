@include('ios::partials.header')

@include('ios::partials.imports')

@objc({{$className}})
public class {{$className}}: ConnectModel {


    {{-- declare relations to one instance --}}
    @each('ios::partials._one_relation', $relations, 'relation') 

    {{-- setup relations --}}
    override public func setupRelations() {
        @each('ios::partials._one_relation_init', $relations, 'relation') 
    }


}


extension {{$className}} {

    {{-- setup typealiases for enums --}}
    @each('ios::partials._enum_type', $members, 'property') 

    @each('ios::partials._property', $members, 'property') 

{{-- @each('ios::partials._relation', $relations, 'relation') --}}

{{-- @each('ios::partials._relation_setters', $relations, 'relation') --}}

    @nonobjc public class func fetchRequest() -> NSFetchRequest<{{$className}}> {
        return NSFetchRequest<{{$className}}>(entityName: "{{$className}}")
    }

}

@each('ios::partials._enum_type_extend', $members, 'property') 
