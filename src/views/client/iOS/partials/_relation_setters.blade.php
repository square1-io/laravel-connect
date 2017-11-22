@if ($relation['many'])
{{-- -- --}}
{{--  --}}
// MARK: Generated accessors for {{$relation['varName']}}
@objc(add{{ucfirst($relation['varName'])}}Object:)
@NSManaged public func addTo{{ucfirst($relation['varName'])}}(_ value: {{$relation['relatedClass']}})

@objc(remove{{ucfirst($relation['varName'])}}Object:)
 @NSManaged public func removeFrom{{ucfirst($relation['varName'])}}(_ value: {{$relation['relatedClass']}})

@objc(add{{ucfirst($relation['varName'])}}:)
@NSManaged public func addTo{{ucfirst($relation['varName'])}}(_ values: NSSet)

@objc(remove{{ucfirst($relation['varName'])}}:)
@NSManaged public func removeFrom{{ucfirst($relation['varName'])}}(_ values: NSSet)

@endif 