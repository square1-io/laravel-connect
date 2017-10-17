
public ModelProperty<{{$member['type']}}> get{{$member['name']}}(){
        return this.{{'_'.$member['varName']}};
}

@if ($member['hasSetter'])
public void set{{$member['name']}}({{$member['type']}} value){
    this.{{'_'.$member['varName']}}.setValue(value);
}
@endif
