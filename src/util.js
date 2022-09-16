export default {
  lpad(s, length, c) {
    s += ''
    while (s.length < length) s = c + s
    return s
  },


  date_string(t) {
    let y = t.getFullYear()
    let m = this.lpad(t.getMonth() + 1, 2, 0)
    let d = this.lpad(t.getDate(), 2, 0)
    return y + '-' + m + '-' + d
  },


  today() {return this.date_string(new Date())},


  add_day(d, x) {
    d = new Date(d)
    d.setDate(d.getDate() + x)
    return this.date_string(d)
  },


  next_day(d) {return this.add_day(d, 1)},
  prev_day(d) {return this.add_day(d, -1)},


  date_cmp(x, y)  {return new Date(x).getTime() - new Date(y).getTime()},
  date_less(x, y) {return this.date_cmp(x, y) < 0},
  date_eq(x, y)   {return this.date_cmp(x, y) == 0},


  to_fixed(l, precision = 1) {
    let result = []

    for (let x of l)
      result.push(isNaN(x) ? x : x.toFixed(precision))

    return result
  },


  exp_avg(l, r = 0.7) {
    let result = []

    let v
    let days = 1
    for (let x of l) {
      if (isNaN(x)) {
        days += 1
        result.push(undefined)

      } else {
        let R = Math.pow(r, days)
        v = isNaN(v) ? x : (R * v + (1 - R) * x)
        result.push(v)
        days = 1
      }
    }

    return result
  },


  to_vector(l, key) {
    let v = []
    for (let e of l) v.push(e[key])
    return v
  },


  sum(l) {
    let t = 0
    for (let x of l) t += x
    return t
  },


  sum_key(l, key) {return this.sum(this.to_vector(l, key))}
}
