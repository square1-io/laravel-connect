


public static LaravelRequest {{$endpoint['requestName']}}({!!$endpoint['requestParams']!!}) {
        LaravelRequest.Builder builder = new LaravelRequest.Builder();
        return builder.method("{{$endpoint['requestMethod']}}")
                      .route("{{$endpoint['requestUri']}}")
                      .paginated({{$endpoint['paginated']}})
                      .modelClass({{$className}}.class)
                      .params({!!$endpoint['requestParamsMap']!!})
                      .build();
}

