
@if ($relation['many'])
@NSManaged public var {{$relation['varName']}}: {{$relation['type']}}
@endif
