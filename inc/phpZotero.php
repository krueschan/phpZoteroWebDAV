<?php
/**
 * @version 0.2
 * @copyright Jeremy Boggs, Sean Takats, 2009-2011
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @package phpZotero
 */

/**
 * Primary class for using the Zotero API.
 *
 * @package phpZotero
 */
class phpZotero {

    const ZOTERO_URI = 'https://api.zotero.org/';

    protected $_apiKey;
    protected $_ch;
    protected $_tmpFile;
    protected $_status = null; 
    protected $_defaultParams;
    
    /**
     * Constructor for the phpZotero object.
     *
     * @param string The private Zotero API key.
     */
    public function __construct($apiKey = null, $defaultParams = array()) {
       $this->_apiKey = $apiKey;
       $this->_defaultParams = $defaultParams;
       if (function_exists('curl_init')) {
           $this->_ch = curl_init();
       } else {
           throw new Exception("You need cURL");
       }
    }

    /**
     * Destructor, closes cURL.
     */
    public function __destruct() {
        curl_close($this->_ch);
        if(isset($this->_tmpFile)) {
          fclose($this->_tmpFile);
        }        
    }

    /**
     * Returns a URL with cURL.
     *
     * @param string The URL.
     * @param string The POST or PUT body. 
     * @param string The HTTP method to use.
     * @param array Headers for the HTTP request.
     */
    protected function _httpRequest($url, $body=NULL, $method='GET', $headers=array()) {
        $ch = $this->_ch;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); //added for running locally on MAMP
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        //earlier versions (prior to update support) assumed non-null $body meant POST
        if(!is_null($body) && ($method != 'PUT') ) {
          $method = 'POST';
        }
        
        switch($method) {
          case 'GET':
            curl_setopt($ch, CURLOPT_HTTPGET, TRUE);
          break;          
          case 'POST':
            curl_setopt($ch, CURLOPT_POST, 1);            
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
          break;
          
          case 'PUT':
            //PHP curl needs a tmp file from which to PUT
            $this->_tmpFile = tmpfile();
            fwrite($this->_tmpFile, $body);
            fseek($this->_tmpFile, 0);
            curl_setopt($ch, CURLOPT_PUT, 1);
            curl_setopt($ch, CURLOPT_INFILE, $this->_tmpFile);
            curl_setopt($ch, CURLOPT_INFILESIZE, strlen($body));            
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
          break;
          
          case 'DELETE':
            curl_setopt($ch, CURLOPT_DELETE, 1);
          break;
        }

        $xml = curl_exec($ch);
        $this->_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if(isset($this->_tmpFile)) {
          fclose($this->_tmpFile);
        }
        return $xml;
    }

    /**
     * Returns a Zotero API feed response.
     *
     * @param string The request.
     * @param array An array of parameters.
     * @param string The HTTP method to use.
     * @param array Headers for the HTTP request.
     */
    protected function _zoteroRequest($request, $parameters = array(), $body=NULL, $method='GET', $headers=array()) {
        $requestUri = $this->_zoteroUri($request, $parameters);
        if ($response = $this->_httpRequest($requestUri, $body, $method, $headers)) {
            return $response;
        }
        return false;
    }

    /**
     * Constructs a valid Zotero URI with query string.
     *
     * @param string The request path.
     * @param array An array of parameters
     * @return string A Zotero URI.
     */
    protected function _zoteroUri($request, $parameters = array())
    {
        $uri = self::ZOTERO_URI . $request;

        $parameters = $this->_filterParams($parameters);

        // If there are parameters, build a query.
        if (count($parameters) > 0) {
            $uri = $uri . '?' . http_build_query($parameters);
        }

        return $uri;
    }

    /**
     * Adds the API key and default parameters to the parameters not already set.
     *
     * @param array An array of parameters.
     * @return array
     */
    protected function _filterParams($parameters = array())
    {
        if (!isset($parameters['key']) && $this->_apiKey) {
            $parameters['key'] = $this->_apiKey;
        }
        foreach($this->_defaultParams as $key=>$value) {
            if (!isset($parameters[$key]) && isset($this->_defaultParams[$key])) {
              $parameters[$key] = $this->_defaultParams[$key];
            }
        }
        return $parameters;
    }

    /**
     * Gets all Zotero items for a user or group library.
     *
     * @param int The Zotero user or group ID.
     * @param array An optional array of parameters.
     * @param string The library type, users or groups
     */
    public function getItems($zoteroId, $parameters = array(), $libraryType="users") {
        return $this->_zoteroRequest("$libraryType/$zoteroId/items", $parameters);
    }

    /**
     * Gets all top-level Zotero items for a user or group.
     *
     * @param int The user or group ID.
     * @param array An optional array of parameters.
     * @param string The library type, users or groups
     */
    public function getItemsTop($zoteroId, $parameters = array(), $libraryType="users") {
        return $this->_zoteroRequest("$libraryType/$zoteroId/items/top", $parameters);
    }

    /**
     * Gets a particular Zotero item by ID.
     *
     * @param int The user or group ID.
     * @param string The item key.
     * @param array An optional array of parameters.
     * @param string The library type, users or groups
     */
    public function getItem($zoteroId, $itemKey, $parameters = array(), $libraryType="users") {
        return $this->_zoteroRequest("$libraryType/$zoteroId/items/$itemKey", $parameters);
    }

    /**
     * Gets a particular Zotero item by ID and etag, to check for updates
     *
     * @param int The user or group ID.
     * @param string The item key.
     * @param string The item etag.
     * @param array An optional array of parameters.
     * @param string The library type, users or groups
     */    
    
    public function getUpdatedItemEtag($zoteroId, $itemKey, $itemEtag, $parameters = array(), $libraryType="users" ) {
        $headers = array("If-Match: $itemEtag");
        return $this->_zoteroRequest("$libraryType/$zoteroId/items/$itemKey", $parameters, null, 'GET', $headers );
    }

    /**
     * Gets a particular Zotero item by ID and modified date, to check for updates
     *
     * @param int The user or group ID.
     * @param string The item key.
     * @param string The header to set for If-Modified-Since, e.g. "2011-04-15T15:45:53+00:00".
     * @param array An optional array of parameters.
     * @param string The library type, users or groups
     */       
    public function getUpdatedItemModified($zoteroId, $itemKey, $updated, $parameters = array(), $libraryType="users" ) {
        $headers = array("If-Modified-Since: $updated");
        return $this->_zoteroRequest("$libraryType/$zoteroId/items/$itemKey", $parameters, null, 'GET', $headers );
    }
    
    /**
     * Gets the tags associated with a given Zotero item.
     *
     * @param int The user or group ID.
     * @param string The item key.
     * @param array An optional array of parameters.
     * @param string The library type, users or groups
     */
    public function getItemTags($zoteroId, $itemKey, $parameters = array(), $libraryType="users") {
        return $this->_zoteroRequest("$libraryType/$zoteroId/items/$itemKey/tags", $parameters);
    }

    /**
     * Gets the children associated with a given Zotero item.
     *
     * @param int The user or group ID.
     * @param string The item key.
     * @param array An optional array of parameters.
     * @param string The library type, users or groups
     */
    public function getItemChildren($zoteroId, $itemKey, $parameters = array(), $libraryType="users") {
        return $this->_zoteroRequest("$libraryType/$zoteroId/items/$itemKey/children", $parameters);
    }

    /**
     * Gets the URI of a user item file.
     *
     * @param int The user or group ID.
     * @param string The item key.
     * @param array Additional parameters for the request.
     * @return string the file URI.
     * @param string The library type, users or groups
     */
    public function getItemFile($zoteroId, $itemKey, $parameters = array(), $libraryType="users") {
        $path = "users/$zoteroId/items/$itemKey/file";
        return $this->_zoteroUri($path, $parameters);
    }

    /**
     * Gets all the collections for a user or group.
     *
     * @param int The user or group ID.
     * @param array An optional array of parameters
     * @param string The library type, users or groups
     */
    public function getCollections($zoteroId, $parameters = array(), $libraryType="users") {
        return $this->_zoteroRequest("$libraryType/$zoteroId/collections", $parameters);
    }

    /**
     * Gets all top-level collections for a user or group.
     *
     * @param int The user or group ID.
     * @param array An optional array of parameters
     * @param string The library type, users or groups
     */
    public function getCollectionsTop($zoteroId, $parameters = array(), $libraryType="users") {
        return $this->_zoteroRequest("$libraryType/$zoteroId/collections/top", $parameters);
    }

    /**
     * Gets a specific collection for a given user or group.
     *
     * @param int The user or group ID.
     * @param string The collection key.
     * @param array An optional array of parameters.
     * @param string The library type, users or groups
     */
    public function getCollection($zoteroId, $collectionKey, $parameters = array(), $libraryType="users") {
        return $this->_zoteroRequest("$libraryType/$zoteroId/collections/$collectionKey", $parameters);
    }

    /**
     * Get the items in a specific collection for a given user or group.
     *
     * @param int The user or group ID.
     * @param string The collection key.
     * @param array An optional array of parameters.
     * @param string The library type, users or groups
     */
    public function getCollectionItems($zoteroId, $collectionKey, $parameters = array(), $libraryType="users") {
        return $this->_zoteroRequest("$libraryType/$zoteroId/collections/$collectionKey/items", $parameters);
    }

    /**
     * Gets the tags for a user or group.
     *
     * @param int The user or group ID.
     * @param array An optional array of parameters.
     * @param string The library type, users or groups
     */
    public function getTags($zoteroId, $parameters = array(), $libraryType="users") {
        return $this->_zoteroRequest("$libraryType/$zoteroId/tags", $parameters);
    }

    /**
     * Gets a specific tag for a user or group.
     *
     * @param int The user or group ID.
     * @param string The tag.
     * @param array An optional array of parameters.
     * @param string The library type, users or groups
     */
    public function getUserTag($zoteroId, $tag, $parameters = array(), $libraryType="users") {
        if($tag = urlencode($tag)) {
            return $this->_zoteroRequest("$libraryType/$zoteroId/tags/$tag", $parameters);
        }
    }

    /**
     * Gets the items tagged with a given tag.
     *
     * @param int The user or group ID.
     * @param string The tag.
     * @param array An optional array of parameters.
     * @param string The library type, users or groups
     */
    public function getTagItems($zoteroId, $tag, $parameters = array(), $libraryType="users") {
        if($tag = urlencode($tag)) {
            return $this->_zoteroRequest("$libraryType/$zoteroId/tags/$tag/items", $parameters);
        }
    }

    /**
     * Gets the groups the API key has access to
     * 
     * @param int $zoteroId the user id
     * @param array $parameters An optional array of parameters
     */
    
    public function getUserGroups($zoteroId, $parameters = array()) {
        return $this->_zoteroRequest("users/$zoteroId/groups", $parameters);
    }
    
    /**
     * Gets a group.
     *
     * @param int The group ID.
     * @param array An optional array of parameters.
     */
    public function getGroup($groupId, $parameters = array())
    {
        return $this->_zoteroRequest("groups/$groupId", $parameters);
    }

    /**
     * Loads XML response into DOM document.
     *
     * @param string The XML response.
     *
     */
     public function getDom($xml) {
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        return $dom;
     }

    /**
     * Gets the start page from the Zotero feed.
     *
     * @param string The DOM output.
     * @param string The rel attribute to find.
     */
    public function getPageStart($dom, $rel) {
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('atom', 'http://www.w3.org/2005/Atom');

        $nextLink = $xpath->evaluate("//atom:link[@rel = '$rel']/@href");
        $nextLinkUrl = $nextLink->item(0)->nodeValue;
        if ($nextLinkUrl) {
            $start = substr(strrchr($nextLinkUrl, '='), 1);
            return $start;
        }
        return false;
    }

    /**
     * Gets the URL for the next page.
     *
     * @param string The DOM output.
     */
    public function getNextPageStart($dom) {
        return $this->getPageStart($dom, 'next');
    }

    /**
     * Gets the URL for the last page.
     *
     * @param string The DOM output.
     */
    public function getLastPageStart($dom) {
        return $this->getPageStart($dom, 'last');
    }

    /**
     * Gets the URL for the first page.
     *
     * @param string The DOM output.
     */
    public function getFirstPageStart($dom) {
        return $this->getPageStart($dom, 'first');
    }

    /**
     * Gets the total results for a specific query.
     *
     * @param string The DOM output.
     */
    public function getTotalResults($dom) {
        $totalResults = $dom->getElementsByTagNameNS('http://zotero.org/ns/api', 'totalResults');
        return $totalResults->item(0)->nodeValue;
    }

    /**
     * Gets the key for a specific query.
     *
     * @param string The DOM output.
     */
    public function getKey($dom) {
        $key = $dom->getElementsByTagNameNS('http://zotero.org/ns/api', 'key');
        return $key->item(0)->nodeValue;
    }

    /**
     * Gets all available item types.
     */
    public function getAllItemTypes() {
        return $this->_zoteroRequest('itemTypes', null, null);
    }

    /**
     * Gets all available item fields.
     */
    public function getAllItemFields() {
        return $this->_zoteroRequest('itemFields', null, null);
    }

    /**
     * Gets valid creator types for a given item type.
     *
     * @param string The item type.
     */
    public function getValidCreatorTypes($itemType) {
        $parameters['itemType'] = $itemType;
        return $this->_zoteroRequest('itemTypeCreatorTypes', $parameters, null);
    }

    /**
     * Gets localized creator fields.
     */
    public function getLocalizedCreatorFields() {
        return $this->_zoteroRequest('creatorFields', null, null);
    }

    /**
     * Gets a template for a given item type
     *
     * @param string The item type.
     */
    public function getItemTemplate($itemType) {
        $parameters['itemType'] = $itemType;
        return $this->_zoteroRequest('items/new', $parameters, null);
    }

    /**
     * Adds an item to a user or group library.
     *
     * @param int The Zotero user or group ID.
     * @param string The item fields, in JSON.
     * @param string The library type, users or groups
     */
    public function createItem($zoteroId, $itemFields) {
        return $this->_zoteroRequest("users/$zoteroId/items", null, $itemFields, 'POST');
    }

    /**
     * Updates an item 
     * 
     * @param int $zoteroId The Zotero user or group ID
     * @param string $itemFields The item fields, in JSON.
     * @param string $itemKey The Zotero item key for the item.
     * @param string $itemEtag The Zotero item etag for the item.
     * @param string $libraryType The library type, users or groups
     */
    public function updateItem($zoteroId, $itemFields, $itemKey, $itemEtag, $libraryType="users") {
        $headers = array("If-Match: $itemEtag");
        return $this->_zoteroRequest("$libraryType/$zoteroId/items/$itemKey", null, $itemFields, 'PUT', $headers );
    }
    
    /**
     * Deletes an item 
     * 
     * @param int $zoteroId The Zotero user or group ID
     * @param string $itemKey The Zotero item key for the item.
     * @param string $itemEtag The Zotero item etag for the item.
     * @param string $libraryType The library type, users or groups
     */
    public function deleteItem($zoteroId, $itemKey, $itemEtag, $libraryType="users") {
        $headers = array("If-Match: $itemEtag");
        return $this->_zoteroRequest("$libraryType/$zoteroId/items/$itemKey", null, $itemFields, 'DELETE', $headers );
    }
    
    
    
    /**
     * Adds items to a library collection.
     *
     * @param int The Zotero user or group ID.
     * @param string The collection key.
     * @param string A space-delimited list of item keys.
     * @param string The library type, users or groups
     */
    public function addItemsToCollection($zoteroId, $collectionKey, $itemKeys, $libraryType="users") {
        return $this->_zoteroRequest("$libraryType/$zoteroId/collections/$collectionKey/items", null, $itemKeys);
    }

    /**
     * Adds a collection to a user or group library.
     *
     * @param int The Zotero user or group ID.
     * @param string The collection fields, in JSON.
     * @param string The library type, users or groups
     */
    public function createCollection($zoteroId, $collectionFields, $libraryType="users") {
        return $this->_zoteroRequest("$libraryType/$zoteroId/collections", null, $collectionFields);
    }

    
    public function getResponseStatus() {
        return $this->_status;
    }
    
    public function getDefaultParam($param) {
      if(isset($this->_defaultParams[$param])) {
        return $this->_defaultParams[$param];
      }
      return FALSE;
    }
}
