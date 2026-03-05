if [ -f storage/test-coverage/coverage.txt ]; then
    echo -e "\033[36mCoverage Report\033[0m"
    awk '/Summary|Classes|Methods|Lines|App|Modules\\/' storage/test-coverage/coverage.txt | while read -r line; do
        # Trường hợp "Methods: ( 0/ 0)" hoặc "Lines: ( 0/ 0)"
        if echo "$line" | grep -qE "Methods: +\( +0/ +0\)" || echo "$line" | grep -qE "Lines: +\( +0/ +0\)"; then
            echo -e "\033[42;30m$line\033[0m" # Nền xanh lá, chữ đen

        # Trường hợp "Methods: 0.00%" hoặc "Lines: 0.00%"
        elif echo "$line" | grep -qE "Methods: +0\.00%" || echo "$line" | grep -qE "Lines: +0\.00%"; then
            echo -e "\033[41;30m$line\033[0m" # Nền đỏ, chữ đen

        # Trường hợp "Classes:"
        elif echo "$line" | grep -qE "Classes: +[0-4][0-9]\.[0-9]+%"; then
            echo -e "\033[41;30m$line\033[0m" # Nền đỏ, chữ đen
        elif echo "$line" | grep -qE "Classes: +[5-8][0-9]\.[0-9]+%"; then
            echo -e "\033[43;30m$line\033[0m" # Nền vàng, chữ đen
        elif echo "$line" | grep -qE "Classes: +(100\.00%|9[0-9]\.[0-9]+%)"; then
            echo -e "\033[42;30m$line\033[0m" # Nền xanh lá, chữ đen

        # Trường hợp "Lines:"
        elif echo "$line" | grep -qE "Lines: +[0-4][0-9]\.[0-9]+%"; then
            echo -e "\033[41;30m$line\033[0m" # Nền đỏ, chữ đen
        elif echo "$line" | grep -qE "Lines: +[5-8][0-9]\.[0-9]+%"; then
            echo -e "\033[43;30m$line\033[0m" # Nền vàng, chữ đen
        elif echo "$line" | grep -qE "Lines: +(100\.00%|9[0-9]\.[0-9]+%)"; then
            echo -e "\033[42;30m$line\033[0m" # Nền xanh lá, chữ đen

        # Trường hợp "Methods"
        elif echo "$line" | grep -qE "Methods: +(100\.00%|9[1-9]\.[0-9]+%)"; then
            echo -e "\033[42;30m$line\033[0m" # Nền xanh lá, chữ đen
        elif echo "$line" | grep -qE "Methods: +[5-8][0-9]\.[0-9]+%"; then
            echo -e "\033[43;30m$line\033[0m" # Nền vàng, chữ đen
        elif echo "$line" | grep -qE "Methods: +[0-4][0-9]\.[0-9]+%"; then
            echo -e "\033[41;30m$line\033[0m" # Nền đỏ, chữ đen

        # Dòng mặc định (không thay đổi màu)
        else
            echo "$line"
        fi
    done
else
    echo -e "\033[41;30mError: Coverage file not found.\033[0m"
    exit 1
fi