
@if (!$relation['many'])
public var rel{{ucfirst($relation['varName'])}}: ConnectOneRelation<{{$relation['type']}}>?
@endif
