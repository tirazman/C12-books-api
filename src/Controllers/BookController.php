<?php 
namespace App\Controllers;   
use App\Repositories\BookRepository;
use App\Validation\Validator; // Make sure to adjust this import namespace based on where Validator.php lives in your project
use Psr\Http\Message\ResponseInterface as Response; 
use Psr\Http\Message\ServerRequestInterface as Request;   

final class BookController 
{ 
    public function __construct(private BookRepository $books) {} 
  
    public function index(Request $r, Response $s): Response { 
        $p   = $r->getQueryParams(); 
        $rows = $this->books->all((string)($p['q'] ?? ''), (int)($p['limit'] ?? 0)); 
        return $this->json($s, ['count'=>count($rows), 'data'=>$rows]); 
    } 

    public function show(Request $r, Response $s, array $a): Response { 
        $book = $this->books->find((int)$a['id']); 
        return $book ? $this->json($s, $book) 
                     : $this->json($s, ['error'=>'not found'], 404); 
    } 

    public function create(Request $r, Response $s): Response { 
        $body = (array)$r->getParsedBody(); 
        
        // --- UPDATED VALIDATION LOGIC FOR STEP 3 ---
        $errors = (new Validator())
            ->required('title', 'author', 'year')
            ->field('title',  Validator::nonEmptyString(200), 'title must be 1-200 chars')
            ->field('author', Validator::nonEmptyString(150), 'author must be 1-150 chars')
            ->field('year',   Validator::intRange(1000, (int)date('Y')), 'year must be 1000..now')
            ->field('genre',  Validator::nonEmptyString(80),  'genre must be ≤ 80 chars')
            ->validate($body);

        if ($errors) return $this->json($s, ['errors' => $errors], 400); 
        // -------------------------------------------

        // FIX: Extract auth 'sub' from JWT and pass it as the second argument
        $auth = (array)$r->getAttribute('auth', []);
        $createdBy = (int)($auth['sub'] ?? 0);

        $id = $this->books->create($body, $createdBy); 
        return $this->json($s, ['message'=>'Book created', 'data'=>$this->books->find($id)], 201)
            ->withHeader('Location', '/api/books/' . $id); 
    } 
  
    /** PUT /api/books/{id} — full or partial update */ 
    /** PUT /api/books/{id} — full or partial update */ 
    public function update(Request $r, Response $s, array $a): Response { 
        $id = (int)$a['id']; 
        $book = $this->books->find($id); 
        if (!$book) return $this->json($s, ['error' => 'Not found'], 404); 
        
        // --- ADDED IDOR CHECK FROM STEP 11 ---
        $auth    = (array)$r->getAttribute('auth', []);
        $isOwner = (int)$book['created_by'] === (int)($auth['sub'] ?? 0);
        $isAdmin = ($auth['role'] ?? 'member') === 'admin';
        
        if (!$isOwner && !$isAdmin) {
            return $this->json($s, ['error' => 'Forbidden'], 403);
        }
        // --------------------------------------

        $body = (array)$r->getParsedBody(); 
        $errors = $this->validate($body, false); 
        if ($errors) return $this->json($s, ['errors' => $errors], 400); 
        
        $this->books->update($id, $body); 
        return $this->json($s, ['message' => 'Book updated', 'data' => $this->books->find($id)]); 
    }

    /** DELETE /api/books/{id} */ 
    public function delete(Request $r, Response $s, array $a): Response { 
        $auth = (array)$r->getAttribute('auth', []); 
        if (($auth['role'] ?? 'member') !== 'admin') { 
            return $this->json($s, ['error' => 'Admins only'], 403); 
        } 
        $id = (int)$a['id']; 
        $book = $this->books->find($id); 
        if (!$book) return $this->json($s, ['error' => "Book {$id} not found"], 404); 
        $this->books->delete($id); 
        return $this->json($s, ['message' => 'Book deleted']);
    }

    // This older validation function remains for the update method unless Step 4 alters it too
    private function validate(array $body, bool $requireAll): array { 
        $errors = []; 
        $rules = [ 
          'title'  => fn($v) => is_string($v) && trim($v) !== '', 
          'author' => fn($v) => is_string($v) && trim($v) !== '', 
          'year'   => fn($v) => is_numeric($v) && (int)$v >= 1000 && (int)$v <= (int)date('Y'), 
        ]; 
        foreach ($rules as $f => $check) { 
            if ($requireAll && !array_key_exists($f, $body)) { $errors[$f]="$f is required"; continue; } 
            if (array_key_exists($f, $body) && !$check($body[$f])) $errors[$f] = "$f is invalid"; 
        } 
        return $errors; 
    } 

    private function json(Response $s, $data, int $status = 200): Response { 
    $s->getBody()->write(json_encode( 
        $data, 
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE 
        | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT 
    )); 
    return $s->withHeader('Content-Type','application/json; charset=utf-8') 
             ->withStatus($status); 
}
}