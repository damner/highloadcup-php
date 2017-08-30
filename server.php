<?php

ini_set('memory_limit', -1);
ini_set('display_errors', true);
error_reporting(-1);

class Users
{
    const FIELDS = ['email', 'first_name', 'last_name', 'gender', 'birth_date'];

    private $users = [];

    private function set(int $id, array $values)
    {
        $this->users[$id] = $values;
    }

    public function getCount(): int
    {
        return count($this->users);
    }

    public function add(array $values): bool
    {
        if (empty($values['id'])) {
            return false;
        }

        foreach (self::FIELDS as $field) {
            if (!array_key_exists($field, $values)) {
                return false;
            }

            if ($values[$field] === null) {
                return false;
            }
        }

        $this->set((int)$values['id'], $values);

        return true;
    }

    public function get(int $id)
    {
        return $this->users[$id] ?? null;
    }

    public function update(int $id, array $values): bool
    {
        if (!count($values)) {
            return false;
        }

        $user = $this->get($id);

        if (!$user) {
            return false;
        }

        foreach (self::FIELDS as $field) {
            if (!array_key_exists($field, $values)) {
                continue;
            }

            if ($values[$field] === null) {
                return false;
            }

            $user[$field] = $values[$field];
        }

        $this->set($id, $user);

        return true;
    }

    public function calculateAges()
    {
        foreach ($this->users as $user) {
            Container::$ages->get($user['birth_date']);
        }
    }
}

class Locations
{
    const FIELDS = ['place', 'country', 'city', 'distance'];

    private $locations = [];

    private function set(int $id, array $values)
    {
        $this->locations[$id] = $values;
    }

    public function getCount(): int
    {
        return count($this->locations);
    }

    public function add(array $values): bool
    {
        if (empty($values['id'])) {
            return false;
        }

        foreach (self::FIELDS as $field) {
            if (!array_key_exists($field, $values)) {
                return false;
            }

            if ($values[$field] === null) {
                return false;
            }
        }

        $this->set((int)$values['id'], $values);

        return true;
    }

    public function get(int $id)
    {
        return $this->locations[$id] ?? null;
    }

    public function update(int $id, array $values): bool
    {
        if (!count($values)) {
            return false;
        }

        $location = $this->get($id);

        if (!$location) {
            return false;
        }

        foreach (self::FIELDS as $field) {
            if (!array_key_exists($field, $values)) {
                continue;
            }

            if ($values[$field] === null) {
                return false;
            }

            $location[$field] = $values[$field];
        }

        $this->set($id, $location);

        return true;
    }
}

class Visits
{
    const FIELDS = ['location', 'user', 'visited_at', 'mark'];

    private $visits = [];

    private function set(int $id, array $values)
    {
        $old = $this->get($id);
        if ($old) {
            if ($values['user'] !== $old['user']) {
                Container::$usersVisits->remove($old['user'], $values['id']);
            }
            if ($values['location'] !== $old['location']) {
                Container::$locationsVisits->remove($old['location'], $values['id']);
            }
        }

        $this->visits[$id] = $values;

        Container::$usersVisits->add($values['user'], $values['id']);
        Container::$locationsVisits->add($values['location'], $values['id']);
    }

    public function getCount(): int
    {
        return count($this->visits);
    }

    public function add(array $values): bool
    {
        if (empty($values['id'])) {
            return false;
        }

        foreach (self::FIELDS as $field) {
            if (!array_key_exists($field, $values)) {
                return false;
            }

            if ($values[$field] === null) {
                return false;
            }
        }

        $this->set((int)$values['id'], $values);

        return true;
    }

    public function get(int $id)
    {
        return $this->visits[$id] ?? null;
    }

    public function update(int $id, array $values): bool
    {
        if (!count($values)) {
            return false;
        }

        $visit = $this->get($id);

        if (!$visit) {
            return false;
        }

        foreach (self::FIELDS as $field) {
            if (!array_key_exists($field, $values)) {
                continue;
            }

            if ($values[$field] === null) {
                return false;
            }

            $visit[$field] = $values[$field];
        }

        $this->set($id, $visit);

        return true;
    }
}

class UsersVisits
{
    private $users = [];

    public function remove(int $userId, int $visitId)
    {
        unset($this->users[$userId][$visitId]);
    }

    public function add(int $userId, int $visitId)
    {
        $this->users[$userId][$visitId] = true;
    }

    public function get(int $userId, $country, $fromDate, $toDate, $toDistance)
    {
        foreach ([$country, $fromDate, $toDate, $toDistance] as $value) {
            if ($value === null) {
                continue;
            }

            if (!is_string($value) || $value === '') {
                return;
            }
        }

        if ($fromDate !== null) {
            if ((string)(int)$fromDate !== $fromDate) {
                return;
            }

            $fromDate = (int)$fromDate;
        }

        if ($toDate !== null) {
            if ((string)(int)$toDate !== $toDate) {
                return;
            }

            $toDate = (int)$toDate;
        }

        if ($toDistance !== null) {
            if ((string)(int)$toDistance !== $toDistance) {
                return;
            }
            
            $toDistance = (int)$toDistance;
        }

        $visitsId = isset($this->users[$userId]) ? array_keys($this->users[$userId]) : [];

        $visits = [];
        foreach ($visitsId as $visitId) {
            $visit = Container::$visits->get($visitId);

            if ($fromDate !== null) {
                if ($visit['visited_at'] <= $fromDate) {
                    continue;
                }
            }

            if ($toDate !== null) {
                if ($visit['visited_at'] >= $toDate) {
                    continue;
                }
            }

            $location = Container::$locations->get($visit['location']);

            if ($country !== null) {
                if ($location['country'] !== $country) {
                    continue;
                }
            }

            if ($toDistance !== null) {
                if ($location['distance'] >= $toDistance) {
                    continue;
                }
            }

            $visits[] = [
                'mark' => $visit['mark'],
                'visited_at' => $visit['visited_at'],
                'place' => $location['place'],
            ];
        }

        usort($visits, function ($a, $b) {
            return $a['visited_at'] - $b['visited_at'];
        });

        return $visits;
    }

    public function sortVisits()
    {
        foreach ($this->users as $userId => $visits) {
            foreach (array_keys($visits) as $visitId) {
                $visits[$visitId] = Container::$visits->get($visitId);
            }

            uasort($visits, function ($a, $b) {
                return $a['visited_at'] - $b['visited_at'];
            });

            $this->users[$userId] = array_map(function () {
                return true;
            }, $visits);
        }
    }
}

class LocationsVisits
{
    private $locations = [];

    public function remove(int $locationId, int $visitId)
    {
        unset($this->locations[$locationId][$visitId]);
    }

    public function add(int $locationId, int $visitId)
    {
        $this->locations[$locationId][$visitId] = true;
    }

    public function avg(int $locationId, $fromDate, $toDate, $fromAge, $toAge, $gender)
    {
        foreach ([$fromDate, $toDate, $fromAge, $toAge, $gender] as $value) {
            if ($value === null) {
                continue;
            }

            if (!is_string($value) || $value === '') {
                return;
            }
        }

        if ($fromDate !== null) {
            if ((string)(int)$fromDate !== $fromDate) {
                return;
            }
            
            $fromDate = (int)$fromDate;
        }

        if ($toDate !== null) {
            if ((string)(int)$toDate !== $toDate) {
                return;
            }
            
            $toDate = (int)$toDate;
        }

        if ($fromAge !== null) {
            if ((string)(int)$fromAge !== $fromAge) {
                return;
            }
            
            $fromAge = (int)$fromAge;
        }

        if ($toAge !== null) {
            if ((string)(int)$toAge !== $toAge) {
                return;
            }
            
            $toAge = (int)$toAge;
        }

        if ($gender !== null) {
            if ($gender !== 'm' && $gender !== 'f') {
                return;
            }
        }

        $visitsId = isset($this->locations[$locationId]) ? array_keys($this->locations[$locationId]) : [];

        $sum = 0;
        $count = 0;
        foreach ($visitsId as $visitId) {
            $visit = Container::$visits->get($visitId);

            if ($fromDate !== null) {
                if ($visit['visited_at'] <= $fromDate) {
                    continue;
                }
            }

            if ($toDate !== null) {
                if ($visit['visited_at'] >= $toDate) {
                    continue;
                }
            }

            if ($fromAge !== null || $toAge !== null || $gender !== null) {
                $user = Container::$users->get($visit['user']);

                if ($gender !== null) {
                    if ($user['gender'] !== $gender) {
                        continue;
                    }
                }
                
                if ($fromAge !== null || $toAge !== null) {
                    $age = Container::$ages->get($user['birth_date']);
                    $age += 0.5;

                    if ($fromAge !== null) {
                        if ($age <= $fromAge) {
                            continue;
                        }
                    }

                    if ($toAge !== null) {
                        if ($age >= $toAge) {
                            continue;
                        }
                    }
                }
            }

            $sum += $visit['mark'];
            $count++;
        }

        return $count === 0 ? 0 : round($sum / $count, 5);
    }
}

class Ages
{
    private $cache = [];
    private $ages = [];

    public function __construct(DateTime $current)
    {
        for ($i = 0; $i < 100; $i++) {
            $this->ages[$i] = $current->modify('-1 year')->getTimestamp();
        }
    }

    public function get(int $timestamp): int
    {
        if (!array_key_exists($timestamp, $this->cache)) {
            $this->cache[$timestamp] = $this->getNoCache($timestamp);
        }

        return $this->cache[$timestamp];
    }

    private function getNoCache(int $timestamp): int
    {
        foreach ($this->ages as $age => $begin) {
            if ($timestamp >= $begin) {
                return $age;
            }
        }

        return 100;
    }
}

function encode($value)
{
    return json_encode($value, JSON_UNESCAPED_UNICODE);
}

class Container
{
    public static $users;
    public static $locations;
    public static $visits;
    public static $usersVisits;
    public static $locationsVisits;
    public static $ages;
}

Container::$users = new Users();
Container::$locations = new Locations();
Container::$visits = new Visits();
Container::$usersVisits = new UsersVisits();
Container::$locationsVisits = new LocationsVisits();
Container::$ages = new Ages(new DateTime());

if (1) {
    system('mkdir -p data2 && cd data2 && unzip -o /tmp/data/data.zip');

    foreach (new DirectoryIterator(__DIR__.'/data2') as $fileInfo) {
        if ($fileInfo->isDot()) {
            continue;
        }

        if ($fileInfo->getExtension() !== 'json') {
            continue;
        }

        $data = json_decode(file_get_contents($fileInfo->getRealPath()), true);

        if (array_key_exists('users', $data)) {
            foreach ($data['users'] as $user) {
                $result = Container::$users->add($user);
                if (!$result) {
                    echo 'Unable to add user:'.PHP_EOL;
                    var_dump($user);
                }
            }
        }

        if (array_key_exists('locations', $data)) {
            foreach ($data['locations'] as $location) {
                $result = Container::$locations->add($location);
                if (!$result) {
                    echo 'Unable to add location:'.PHP_EOL;
                    var_dump($location);
                }
            }
        }

        if (array_key_exists('visits', $data)) {
            foreach ($data['visits'] as $visit) {
                $result = Container::$visits->add($visit);
                if (!$result) {
                    echo 'Unable to add visit:'.PHP_EOL;
                    var_dump($visit);
                }
            }
        }
    }

    Container::$users->calculateAges();

    Container::$usersVisits->sortVisits();

    echo 'Users: '.Container::$users->getCount().PHP_EOL;
    echo 'Locations: '.Container::$locations->getCount().PHP_EOL;
    echo 'Visits: '.Container::$visits->getCount().PHP_EOL;
    echo 'Memory: '.round(memory_get_peak_usage() / 1024 / 1024, 1).'Mb'.PHP_EOL;
}

gc_collect_cycles();
gc_disable();

$port = $argv[1] ?? '80';
echo 'Server running at port '.$port.PHP_EOL;

$http = new swoole_http_server('0.0.0.0', $port, SWOOLE_BASE);

$http->set([
    'worker_num' => 1,
    'open_tcp_nodelay' => true,
    'http_parse_post' => false,
    // 'open_tcp_keepalive' => true,
    // 'tcp_keepinterval' => 300,
    // 'tcp_keepidle' => 300,
]);

$http->on('request', function (swoole_http_request $request, swoole_http_response $response) {
    $method = $request->server['request_method'];
    $path = $request->server['request_uri'];
    $body = $method === 'POST' ? $request->rawContent() : null;
    $values = $body === null ? null : (array)json_decode($body, true);
    $params = $request->get ?? [];

    $response->header('Content-Type', 'application/json');

    // if ($method === 'POST') {
        // $response->header('Connection', 'close');
    // }

    // Users

    $prefix_length = 7;

    if ($method === 'GET' && substr($path, 0, $prefix_length) === '/users/') {
        $id = substr($path, $prefix_length);
        if ((string)(int)$id === $id) {
            $user = Container::$users->get((int)$id);
            if ($user) {
                $response->status(200);
                $response->end(encode($user));

                return;
            }

            return $response->status(404);
        }
    }

    if ($method === 'GET' && substr($path, 0, $prefix_length) === '/users/') {
        if (substr($path, -7) === '/visits') {
            $id = substr($path, $prefix_length, -7);
            if ((string)(int)$id === $id) {
                $id = (int)$id;
                $user = Container::$users->get($id);
                if ($user) {
                    $visits = Container::$usersVisits->get($id, $params['country'] ?? null, $params['fromDate'] ?? null, $params['toDate'] ?? null, $params['toDistance'] ?? null);

                    if ($visits === null) {
                        return $response->status(400);
                    }

                    $response->status(200);
                    $response->end(encode([
                        'visits' => $visits,
                    ]));

                    return;
                }

                return $response->status(404);
            }
        }
    }

    if ($method === 'POST' && $path === '/users/new') {
        $result = Container::$users->add($values);
        if ($result) {
            $response->status(200);
            $response->end('{}');
            
            return;
        }

        return $response->status(400);
    }

    if ($method === 'POST' && substr($path, 0, $prefix_length) === '/users/') {
        $id = substr($path, $prefix_length);
        if ((string)(int)$id === $id) {
            $id = (int)$id;
            $user = Container::$users->get($id);
            if ($user) {
                $result = Container::$users->update($id, $values);
                if ($result) {
                    $response->status(200);
                    $response->end('{}');
                    
                    return;
                }

                return $response->status(400);
            }
        }
    }

    // Locations

    $prefix_length = 11;

    if ($method === 'GET' && substr($path, 0, $prefix_length) === '/locations/') {
        $id = substr($path, $prefix_length);
        if ((string)(int)$id === $id) {
            $location = Container::$locations->get((int)$id);
            if ($location) {
                $response->status(200);
                $response->end(encode($location));
                
                return;
            }

            return $response->status(404);
        }
    }

    if ($method === 'GET' && substr($path, 0, $prefix_length) === '/locations/') {
        if (substr($path, -4) === '/avg') {
            $id = substr($path, $prefix_length, -4);
            if ((string)(int)$id === $id) {
                $id = (int)$id;
                $location = Container::$locations->get($id);
                if ($location) {
                    $avg = Container::$locationsVisits->avg($id, $params['fromDate'] ?? null, $params['toDate'] ?? null, $params['fromAge'] ?? null, $params['toAge'] ?? null, $params['gender'] ?? null);

                    if ($avg === null) {
                        return $response->status(400);
                    }

                    $response->status(200);
                    $response->end(encode([
                        'avg' => $avg,
                    ]));
                    
                    return;
                }

                return $response->status(404);
            }
        }
    }

    if ($method === 'POST' && $path === '/locations/new') {
        $result = Container::$locations->add($values);
        if ($result) {
            $response->status(200);
            $response->end('{}');

            return;
        }

        return $response->status(400);
    }

    if ($method === 'POST' && substr($path, 0, $prefix_length) === '/locations/') {
        $id = substr($path, $prefix_length);
        if ((string)(int)$id === $id) {
            $id = (int)$id;
            $location = Container::$locations->get($id);
            if ($location) {
                $result = Container::$locations->update($id, $values);
                if ($result) {
                    $response->status(200);
                    $response->end('{}');
                    
                    return;
                }

                return $response->status(400);
            }
        }
    }

    // Visits

    $prefix_length = 8;

    if ($method === 'GET' && substr($path, 0, $prefix_length) === '/visits/') {
        $id = substr($path, $prefix_length);
        if ((string)(int)$id === $id) {
            $visit = Container::$visits->get((int)$id);
            if ($visit) {
                $response->status(200);
                $response->end(encode($visit));
                
                return;
            }

            return $response->status(404);
        }
    }

    if ($method === 'POST' && $path === '/visits/new') {
        $result = Container::$visits->add($values);
        if ($result) {
            $response->status(200);
            $response->end('{}');
            
            return;
        }

        return $response->status(400);
    }

    if ($method === 'POST' && substr($path, 0, $prefix_length) === '/visits/') {
        $id = substr($path, $prefix_length);
        if ((string)(int)$id === $id) {
            $id = (int)$id;
            $visit = Container::$visits->get($id);
            if ($visit) {
                $result = Container::$visits->update($id, $values);
                if ($result) {
                    $response->status(200);
                    $response->end('{}');
                    
                    return;
                }

                return $response->status(400);
            }
        }
    }

    return $response->status(404);
});

$http->start();
