if redis.call('get',KEYS[1] == ARGV[1]) then
    return redis.pcall('del',KEYS[1])
else
    return 0
end