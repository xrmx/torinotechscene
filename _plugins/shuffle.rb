# From http://stackoverflow.com/questions/27179385/how-do-i-shuffle-the-order-of-an-array-in-jekyll

module Jekyll
  module ShuffleFilter
    def shuffle(array)
      array.shuffle
    end
  end
end

Liquid::Template.register_filter(Jekyll::ShuffleFilter)
