"""Cek cepat penggabungan dua peringkat di `_retrieve`.

Jalankan: cd service && .venv/bin/python test_retrieve.py
"""

import app


def main() -> None:
    app._vector = lambda q, limit=8: [app._hit("A", "u1", "a" * 100), app._hit("B", "u2", "b" * 100)]
    app._keyword = lambda q, limit=8: [app._hit("B", "u2", "b" * 100), app._hit("C", "u3", "c" * 100)]

    got = [hit["title"] for hit in app._retrieve("x")]

    assert got[0] == "B", got                       # ditemukan kedua cara, naik ke atas
    assert sorted(got) == ["A", "B", "C"], got      # tidak ada hasil yang hilang
    print("ok:", got)


if __name__ == "__main__":
    main()
