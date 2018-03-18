
@if ($property['type'] == 'enum' )
public typealias {{ucfirst($property['varName'])}} = String
@endif
