
@if (!$relation['many'])
public var rel{{ucfirst($relation['varName'])}}: ConnectOneRelation<{{$relation['type']}}>!
@else
public var rel{{ucfirst($relation['varName'])}}: ConnectManyRelation<{{$relation['type']}}>!
@endif
