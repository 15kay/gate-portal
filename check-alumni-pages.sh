#!/bin/bash
# Check which alumni pages have /gate-portal/ references

echo "=========================================="
echo "Checking Alumni Pages for /gate-portal/"
echo "=========================================="
echo ""

cd /var/www/html/gate-portal

echo "Scanning alumni directory..."
echo ""

# Check each alumni PHP file
for file in alumni/*.php; do
    if [ -f "$file" ]; then
        COUNT=$(grep -c "/gate-portal/" "$file" 2>/dev/null || echo "0")
        if [ "$COUNT" -gt 0 ]; then
            echo "✗ $file - Found $COUNT occurrence(s)"
            echo "  Lines:"
            grep -n "/gate-portal/" "$file" | head -5
            echo ""
        else
            echo "✓ $file - Clean"
        fi
    fi
done

echo ""
echo "Checking includes directory..."
echo ""

# Check includes files
for file in includes/*.php; do
    if [ -f "$file" ]; then
        COUNT=$(grep -c "/gate-portal/" "$file" 2>/dev/null || echo "0")
        if [ "$COUNT" -gt 0 ]; then
            echo "✗ $file - Found $COUNT occurrence(s)"
            echo "  Lines:"
            grep -n "/gate-portal/" "$file" | head -5
            echo ""
        fi
    fi
done

echo ""
echo "Checking auth directory..."
echo ""

# Check auth files
for file in auth/*.php; do
    if [ -f "$file" ]; then
        COUNT=$(grep -c "/gate-portal/" "$file" 2>/dev/null || echo "0")
        if [ "$COUNT" -gt 0 ]; then
            echo "✗ $file - Found $COUNT occurrence(s)"
            echo "  Lines:"
            grep -n "/gate-portal/" "$file" | head -5
            echo ""
        fi
    fi
done

echo ""
echo "=========================================="
echo "Summary"
echo "=========================================="

TOTAL=$(grep -r "/gate-portal/" alumni/ includes/ auth/ --include="*.php" 2>/dev/null | wc -l)
echo "Total /gate-portal/ occurrences: $TOTAL"

if [ $TOTAL -gt 0 ]; then
    echo ""
    echo "Files with issues:"
    grep -r "/gate-portal/" alumni/ includes/ auth/ --include="*.php" -l 2>/dev/null | sort
fi

echo ""
