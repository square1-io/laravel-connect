
@if ($property['type'] == 'enum' )
extension  {{$className}}.{{ucfirst($property['varName'])}} {
    
    @foreach($property['allowedValues'] as $allowedValue)
        public static let {{strtoupper($allowedValue)}}:{{$className}}.{{ucfirst($property['varName'])}} = "{{$allowedValue}}"
    @endforeach

}
@endif
