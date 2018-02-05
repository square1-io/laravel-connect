
@if (!$relation['many'])
    self.rel{{ucfirst($relation['varName'])}} = self.setupRelation(name:"{{$relation['varName']}}")
@endif
