@if ($relation['many'])
    {{'_'.$relation['varName']}} = ({!!$relation['type']!!})addRelation("{{$relation['name']}}", {{$relation['relatedClass']}}.class);
@else
    {{'_'.$relation['varName']}} = ({!!$relation['type']!!})addRelation("{{$relation['name']}}","{{$relation['key']}}", {{$relation['relatedClass']}}.class);
@endif    