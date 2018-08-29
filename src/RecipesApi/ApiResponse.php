<?php

namespace RecipesApi;

use Exception;
use LogicException;
use InvalidArgumentException;
use BadMethodCallException;
use Model;

class ApiResponse
{
    const apiRoot = 'http://localhost'; //'http://api.localhost/v1';

    const route_auth = '/auth';
    const route_list = '/recipes';
    const route_create = '/recipes';
    const route_get = '/recipes';
    const route_update = '/recipes';
    const route_delete = '/recipes';
    const route_rate = '/rating';
    const route_search = '/recipes/search/';

    protected $request = null;
    protected $response = null;
    protected $isAuthNeeded = true;
    protected $apiVersion = '1.0';
    protected $status = 'running';
    protected $auth = null;
    protected $listing = [];
    protected $perPageDefault = 3;
    protected $name = '';
    protected $id = 0;
    protected $dbDocuments = null;
    protected $cache = null;
    protected $page;

    // creating an ApiRequest objecnt and init it
    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
        $this->auth = new Auth();


        $this->dbDocuments = new ModelMongo();
        $this->cache = new ModelRedis();
        $this->dbRate = new ModelPostgres();
        $this->page = new Page();
    }

    public function __destruct()
    {
        if (isset($this->dbUsers)) {
            unset($this->dbUsers);
        }

        if (isset($this->dbDocuments)) {
            unset($this->dbDocuments);
        }

        if (isset($this->cache)) {
            unset($this->cache);
        }

        if (isset($this->auth)) {
            unset($this->auth);
        }
    }

    public function getResponse()
    {
        try {
            # 403 User agent required. All API requests MUST include a valid User-Agent header. Requests with no User-Agent header will be rejected. We request that you use your username, or the name of your application, for the User-Agent header value. This allows us to contact you if there are problems. https://developer.github.com/v3/#pagination
            if (empty($this->request->getUserAgent())) {
                throw new InvalidArgumentException("User agent required. Request forbidden by administrative rules. Please make sure your request has a User-Agent header.",
                    403);
            }

            # 405 (Method Not Allowed). A 405 response must include the Allow header, which lists the HTTP methods that the resource supports.
            if (!$this->request->isMethodAllowed()) {
                throw new BadMethodCallException("Method Not Allowed. Please make sure your request has a allowed method header.",
                    405);
            }

            $methodName = $this->getRoute();

            if ($methodName) {
                $item = $this->$methodName();
                $item ? $this->response->setParameters($item) : $this->response->setParameters([]);
                $this->response->send();
            } else {
                throw new LogicException("Route is not found. URI: " . $this->request->getUri() . ", method: " . $this->request->getMethodType() . ", id: " . $this->request->getGetParam("id"),
                    404);
            }
        } catch (Exception $e) {
            if ($e->getCode()) {
                $this->response->setError($e->getCode(), $e->getMessage());
            } else {
                $this->response->setError(500, $e->getMessage());
            }
            $this->response->send();
        }
    }

    // routing - check the request and understand which response is needed

    public function getRoute()
    {
        $this->name = '';

        $matches = [];
        if (preg_match('/[a-f\d]{24}/i', $this->request->getUri(), $matches)) {
            $this->id = reset($matches); # get first param in URI after /recipes/
            $this->request->setGetParam("id", $this->id);
        }

        if ($this->request->getMethodType() == Request::METHOD_POST && strpos($this->request->getUri(),
                self::route_auth) !== false) {
            $this->name = 'auth';
            $this->isAuthNeeded = false;
        }

        //	List	GET	/recipes	✘
        if ($this->request->getMethodType() == Request::METHOD_GET
            && strpos($this->request->getUri(), self::route_list) !== false
            && !preg_match('/[a-f\d]{24}/i', $this->request->getUri())
        ) {
            $this->name = 'listing';
            $this->isAuthNeeded = false;
        }

        // Create	POST	/recipes	✓
        if ($this->request->getMethodType() == Request::METHOD_POST
            && strpos($this->request->getUri(), self::route_create) !== false
            && (empty($this->request->getGetParam("id")) || is_null($this->request->getGetParam("id")))) {
            $this->isAuthNeeded = true;
            $this->name = 'create';
        }

        // Get	GET	/recipes/{id}	✘
        if ($this->request->getMethodType() == Request::METHOD_GET
            && strpos($this->request->getUri(), self::route_list) !== false
            && preg_match('/[a-f\d]{24}$/i', $this->request->getUri())) {
            $this->isAuthNeeded = false;
            $this->name = 'get';

            $matches = [];
            if (preg_match('/[a-f\d]{24}$/i', $this->request->getUri(), $matches)) {
                $this->id = reset($matches); # get first param in URI after /recipes/
                $this->request->setGetParam("id", $this->id);
            }
        }

        // Update	PUT	/recipes/{id}	✓
        if ($this->request->getMethodType() == Request::METHOD_PUT
            && strpos($this->request->getUri(), self::route_list) !== false
            && preg_match('/[a-f\d]{24}$/i', $this->request->getUri())) {
            $this->isAuthNeeded = true;
            $this->name = 'update';

            if ($this->request->getContentType() != 'application/x-www-form-urlencoded') {
                throw new Exception("Accept only application/x-www-form-urlencoded for PUT method", 415);
            }

            $matches = [];
            if (preg_match('/[a-f\d]{24}$/i', $this->request->getUri(), $matches)) {
                $this->id = reset($matches); # get first param in URI after /recipes/
                $this->request->setGetParam("id", $this->id);
            }
        }

        // Update	PATCH	/recipes/{id}	✓
        if ($this->request->getMethodType() == Request::METHOD_PATCH
            && strpos($this->request->getUri(), self::route_update) !== false
            && preg_match('/[a-f\d]{24}$/i', $this->request->getUri(), $id)) {
            $this->isAuthNeeded = true;
            $this->name = 'patch';

            $matches = [];
            if (preg_match('/[a-f\d]{24}$/i', $this->request->getUri(), $matches)) {
                $this->id = reset($matches); # get first param in URI after /recipes/
                $this->request->setGetParam("id", $this->id);
            }
        }

        // Delete	DELETE	/recipes/{id}	✓
        if ($this->request->getMethodType() == Request::METHOD_DELETE
            && strpos($this->request->getUri(), self::route_delete) !== false
            && preg_match('/[a-f\d]{24}$/i', $this->request->getUri(), $id)) {
            $this->isAuthNeeded = true;
            $this->name = 'delete';

        }

        // Rate	POST	/recipes/{id}/rating	✘
        if ($this->request->getMethodType() == Request::METHOD_POST
            && strpos($this->request->getUri(), self::route_rate) !== false
            && preg_match('/[a-f\d]{24}/i', $this->request->getUri())) {
            $this->isAuthNeeded = false;
            $this->name = 'rate';
        }

        // SEARCH	POST	/recipes/search	✘
        if ($this->request->getMethodType() == Request::METHOD_GET
            && strpos($this->request->getUri(), self::route_search) !== false) {
            $this->isAuthNeeded = false;
            $this->name = 'search';
        }

        return $this->name;
    }

    public function isAuthNeeded()
    {
        return $this->isAuthNeeded;
    }

    public function auth()
    {
        $login = $this->request->getPostParam('login');
        $password = $this->request->getPostParam('password');

        if ($this->auth->login($login, $password)) {
            $this->response->setStatusCode(200);
            $this->response->addHttpHeaders([$this->auth->getAuthHeaderName() => $this->auth->getAuthHeaderValue()]);
        } else {
            $this->response->setStatusCode(401);
            $this->response->setHttpHeader("WWW-Authenticate", $this->auth->getAllowedAuth());
        }
    }

    public function get()
    {
        if ($this->id) {
            $recipe = new Recipe();
            $recipe->setId((string)$this->id);

            $item = $this->dbDocuments->getId($recipe);

            if (isset($recipe)) {
                unset($recipe);
            }

            if ($item) {
                $this->response->setStatusCode(200);
                return $item;
            } else {
                $this->response->setStatusCode(404);
            }
        }
    }

    public function listing()
    {
        # pagination header
        $pageHeader = $this->getPaginationLink(); # initialize page
        $this->response->addHttpHeaders($pageHeader);

        $listing = $this->dbDocuments->getListing($this->page);

        $this->response->setStatusCode(200);

        if (isset($page)) {
            unset($page);
        }

        return $listing; // add pagination
    }

    public function getPaginationLink()
    {
        $apiRequestUrl = self::apiRoot . self::route_list;

        $this->page->page = $this->request->getGetParam("page") ? $this->request->getGetParam("page") : 1;
        $this->page->per_page = $this->request->getGetParam("per_page") ? (int)$this->request->getGetParam("per_page") : $this->perPageDefault;
        $this->page->prev = ($this->page->page > 2) ? $this->page->page - 1 : 0;
        $this->page->next = $this->page->page + 1;
        $this->page->skip = ($this->page->page - 1) * $this->page->per_page;

        $hPrev = $this->page->prev ? "<$apiRequestUrl?page=" . $this->page->prev . "&per_page=" . $this->page->per_page . ">; rel=\"prev\"," : '';

        return ["Link" => "<$apiRequestUrl?page=" . $this->page->next . "&per_page=" . $this->page->per_page . ">; rel=\"next\", $hPrev <$apiRequestUrl?page=1&per_page=" . $this->page->per_page . ">; rel=\"first\""];
        //,<$apiRequestUrl?page=".$this->page->last_page."&per_page=".$this->page->per_page.">; rel=\"last\""]; # last page won't be calculated because of the highload reason
    }


    // OK
    public function create()
    {
        $this->auth->DecodeToken($this->request->getToken());

        $recipe = new Recipe();
        $recipe->setName($this->request->getPostParam("name"));
        $recipe->setPreptime($this->request->getPostParam("preptime"));
        $recipe->setDifficulty($this->request->getPostParam("difficulty"));
        $recipe->setVegeterian($this->request->getPostParam("vegeterian"));

        if (!count($this->dbDocuments->getListing(new Page(), ['name' => $recipe->getName()]))) {
            $id = $this->dbDocuments->setId($recipe);
            $item = ["id" => $id, "uri" => self::route_update . "/" . $id];
            $this->response->setStatusCode(201);
        } else {
            if (isset($recipe)) {
                unset($recipe);
            }
            throw new Exception("Item already exists", 409);
        }

        if (isset($recipe)) {
            unset($recipe);
        }
        return $item;
    }

    public function update()
    {
        $this->auth->DecodeToken($this->request->getToken());

        $put = array();
        parse_str(file_get_contents('php://input'), $put);

        $this->request->setPutParams($put);

        if (!$this->request->getPutParam("name")
            || !$this->request->getPutParam("id")
            || !$this->request->getPutParam("preptime")
            || !$this->request->getPutParam("difficulty")
            || !$this->request->getPutParam("vegeterian")
        ) {
            throw new Exception("Some required parameters are not found. Need all params for the Recipe class.", 400);
        }

        $rcp = new Recipe();

        if ($this->request->getPutParam("name") !== null) {
            $rcp->setName($this->request->getPutParam("name"));
        }

        if ($this->request->getPutParam("preptime") !== null) {
            $rcp->setPreptime($this->request->getPutParam("preptime"));
        }

        if ($this->request->getPutParam("difficulty") !== null) {
            $rcp->setDifficulty($this->request->getPutParam("difficulty"));
        }

        if ($this->request->getPutParam("vegeterian") !== null) {
            $rcp->setVegeterian($this->request->getPutParam("vegeterian"));
        }

        if ($this->request->getPutParam("id") !== null) {
            $rcp->setId($this->request->getPutParam("id"));
        }

        $this->dbDocuments->update($rcp);

        $this->response->setStatusCode(200);
        if (isset($recipe)) {
            unset($recipe);
        }
    }

    public function patch()
    {
        $this->auth->DecodeToken($this->request->getToken());

        $put = array();
        parse_str(file_get_contents('php://input'), $put);

        $this->request->setPutParams($put);

        if (!$this->id) {
            throw new Exception("ID of record must exist in URI", 400);
        }

        if (!$this->request->getPutParam("name")
            && !$this->request->getPutParam("preptime")
            && !$this->request->getPutParam("difficulty")
            && !$this->request->getPutParam("vegeterian")
        ) {
            throw new Exception("At least one parameter should be updated in record.", 400);
        }

        $rcp = new Recipe();

        if ($this->request->getPutParam("name") !== null) {
            $rcp->setName($this->request->getPutParam("name"));
        }

        if ($this->request->getPutParam("preptime") !== null) {
            $rcp->setPreptime($this->request->getPutParam("preptime"));
        }

        if ($this->request->getPutParam("difficulty") !== null) {
            $rcp->setDifficulty($this->request->getPutParam("difficulty"));
        }

        if ($this->request->getPutParam("vegeterian") !== null) {
            $rcp->setVegeterian($this->request->getPutParam("vegeterian"));
        }

        if ($this->request->getPutParam("id") !== null) {
            $rcp->setId($this->request->getPutParam("id"));
        }

        $this->dbDocuments->update($rcp);

        $this->response->setStatusCode(200);
        if (isset($recipe)) {
            unset($recipe);
        }
    }

    public function delete()
    {
        $this->auth->DecodeToken($this->request->getToken());

        if (!$this->id) {
            throw new Exception("Object not found", 404);
        }

        $recipe = new Recipe();
        $recipe->setId((string)$this->id);

        $this->dbDocuments->delete($recipe);
        $this->response->setStatusCode(204, "Object deleted ($this->id)");
        if (isset($recipe)) {
            unset($recipe);
        }

    }

    public function rate()
    {
        $recipe = new Recipe();
        $recipe->setId((string)$this->id);
        $recipe->setRate((int)$this->request->getPostParam("rate"));
        $this->dbRate->rate($recipe);
        $this->response->setStatusCode(200);
        if (isset($recipe)) {
            unset($recipe);
        }
    }

    public function search()
    {
        if (!$this->request->getGetParam("name")
            && !$this->request->getGetParam("preptime")
            && !$this->request->getGetParam("difficulty")
            && !$this->request->getGetParam("vegeterian")
        ) {
            throw new Exception("Empty search string", 400);
        }

        # pagination header
        $pageHeader = $this->getPaginationLink(); # initialize page
        $this->response->addHttpHeaders($pageHeader);

        $rcp = new Recipe();
        $filter = [];

        if ($this->request->getGetParam("name") !== null) {
            $rcp->setName($this->request->getGetParam("name"));
            $filter['name'] = $rcp->getName();
        }

        if ($this->request->getGetParam("preptime") !== null) {
            $rcp->setPreptime($this->request->getGetParam("preptime"));
            $filter['preptime'] = $rcp->getPreptime();
        }

        if ($this->request->getGetParam("difficulty") !== null) {
            $rcp->setDifficulty($this->request->getGetParam("difficulty"));
            $filter['difficulty'] = $rcp->getDifficulty();
        }

        if ($this->request->getGetParam("vegeterian") !== null) {
            $rcp->setVegeterian($this->request->getGetParam("vegeterian"));
            $filter['vegeterian'] = $rcp->getVegeterian();
        }

        $res = $this->dbDocuments->getListing($this->page, $filter);
        $this->response->setStatusCode(200);
        if (isset($recipe)) {
            unset($recipe);
        }

        return $res;
    }

    public function v()
    {
        return json_encode(["status" => $this->status, "version" => $this->apiVersion, "api" => ApiResponse::apiRoot]);
    }
}

?>
