#!/bin/bash

URL="http://localhost:8000/articles/1/comments"
NUM_REQUESTS=100
CSRF_TOKEN="smDj0QeOvDKucPgrsFQeVI4t3H2P5gqVKNOab8XM"
SESSION_COOKIE="laravel_session=eyJpdiI6IkNYc1JQdDFjdWtYRXh5aFpGOUNUV1E9PSIsInZhbHVlIjoiTUlJYmlTM1FJNTFsSHpIcmo2Q0d1alliVDkzRzdlaWk0SjlueHk4UGlWbVZUdG1ybzA0WXNZQmJ5TGJXWHVQbFBnazJiZmc2UlFWRFF2UGxJSUZGUjFvQVJwWjVvYmlXVG13RUZYOWJ0NUF2My9pelh1akp4YVF2bGhwYU52QUkiLCJtYWMiOiIzMDA5ODczMTQwOTM2ZjRmOGU0NTNmYzMwZDI2ZWJhMmY0YTFkMjNlYjNiZDU5Yzk0N2ViZTk2OTE5M2ViOTFjIiwidGFnIjoiIn0%3D"

send_comment(){
    local comment_number=$1
    curl -s -H "Cookie: $SESSION_COOKIE" -X POST -d "content= commento casuale $1&_token=$CSRF_TOKEN" "$URL"
}

for ((i=1; i<=NUM_REQUESTS; i++))
do
    send_comment $i
    if [$((i % 5))-eq 0]; then
        "sleep 60"
    fi
    echo "Comment $i sent"
done
