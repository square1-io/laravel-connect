    self.{{$property['varName']}} = try values.decode({{$property['type']}}.self, forKey: .{{$property['json_key']}})
