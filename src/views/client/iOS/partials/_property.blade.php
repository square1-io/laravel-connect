@if ($property['type'] == 'enum' )
@NSManaged public var {{$property['varName']}}: {{ucfirst($property['varName'])}}
@else  
@NSManaged public var {{$property['varName']}}: {{$property['type']}}
@endif
